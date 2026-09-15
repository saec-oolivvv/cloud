<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Encryption;
use Saec\Core\JWTHandler;
use Saec\Services\MountService;
use Saec\Services\StorageService;

/**
 * SyncController — API du client desktop SAEC Sync
 *
 * Deux rôles:
 * 1. Device Code flow OAuth (POST /api/auth/device, POST /api/auth/token)
 * 2. API sync fichier (GET/POST/PUT/DELETE /api/sync/mounts/...)
 *
 * Les blobs sont stockés CHIFFRÉS (AES-256-GCM) sur le provider distant.
 * Le client sync lit/écrit en clair; le serveur chiffre/déchiffre.
 * `sync_files` indexe les fichiers par (tenant, mount, path) + file_key.
 */
class SyncController extends Controller
{
    private const DEVICE_TTL = 900;      // 15 min
    private const DEVICE_POLL = 5;       // intervalle de polling (s)

    private function db(): Database
    {
        return Database::getInstance();
    }

    // ═══════════════════════════════════════════════════════
    // DEVICE CODE FLOW (API)
    // ═══════════════════════════════════════════════════════

    /**
     * POST /api/auth/device — initier un flux device code
     */
    public function deviceCode(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'invalid_request'], 405);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $clientId = trim($body['client_id'] ?? '');
        if (empty($clientId)) {
            $this->json(['error' => 'invalid_request', 'error_description' => 'client_id requis'], 400);
            return;
        }

        $deviceCode = bin2hex(random_bytes(32));
        $userCode = $this->generateUserCode();

        try {
            $this->db()->insert('device_codes', [
                'device_code' => $deviceCode,
                'user_code' => $userCode,
                'client_id' => substr($clientId, 0, 255),
                'scope' => isset($body['scope']) ? substr((string) $body['scope'], 0, 255) : null,
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', time() + self::DEVICE_TTL),
            ]);
        } catch (\Throwable $e) {
            error_log("[DeviceFlow] Insert failed: {$e->getMessage()}");
            $this->json(['error' => 'internal_error'], 500);
            return;
        }

        $base = $this->baseUrl();

        $this->json([
            'device_code' => $deviceCode,
            'user_code' => $userCode,
            'verification_uri' => $base . '/device',
            'verification_uri_complete' => $base . '/device?code=' . urlencode($userCode),
            'expires_in' => self::DEVICE_TTL,
            'interval' => self::DEVICE_POLL,
        ]);
    }

    /**
     * POST /api/auth/token — échanger device_code ou refresh_token contre tokens JWT
     */
    public function token(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'invalid_request'], 405);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $grantType = $body['grant_type'] ?? '';

        if ($grantType === 'urn:ietf:params:oauth:grant-type:device_code') {
            $this->tokenFromDeviceCode($body);
            return;
        }

        if ($grantType === 'refresh_token') {
            $this->tokenFromRefresh($body);
            return;
        }

        $this->json(['error' => 'unsupported_grant_type'], 400);
    }

    private function tokenFromDeviceCode(array $body): void
    {
        $deviceCode = $body['device_code'] ?? '';
        if (empty($deviceCode)) {
            $this->json(['error' => 'invalid_request', 'error_description' => 'device_code requis'], 400);
            return;
        }

        $db = $this->db();
        $row = $db->fetch(
            "SELECT * FROM device_codes WHERE device_code = ? AND expires_at > NOW()",
            [$deviceCode]
        );

        if (!$row) {
            $this->json(['error' => 'expired_token', 'error_description' => 'Code expiré ou invalide'], 400);
            return;
        }

        if ($row['status'] === 'pending') {
            $db->execute(
                "UPDATE device_codes SET last_polled_at = NOW() WHERE id = ?",
                [$row['id']]
            );
            $this->json(['error' => 'authorization_pending', 'error_description' => 'En attente d\'approbation'], 400);
            return;
        }

        if ($row['status'] !== 'approved' || empty($row['user_id'])) {
            $this->json(['error' => 'access_denied', 'error_description' => 'Accès refusé'], 400);
            return;
        }

        $user = $db->fetch(
            "SELECT id, tenant_id, email, role FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL",
            [(int) $row['user_id']]
        );

        if (!$user) {
            $this->json(['error' => 'invalid_user'], 400);
            return;
        }

        $this->issueTokens($user);
        $db->execute("DELETE FROM device_codes WHERE id = ?", [$row['id']]);
    }

    private function tokenFromRefresh(array $body): void
    {
        $refresh = $body['refresh_token'] ?? '';
        if (empty($refresh)) {
            $this->json(['error' => 'invalid_request', 'error_description' => 'refresh_token requis'], 400);
            return;
        }

        $payload = JWTHandler::decode($refresh);
        if (!$payload || !isset($payload->sub) || ($payload->type ?? '') !== 'refresh') {
            $this->json(['error' => 'invalid_grant', 'error_description' => 'Refresh token invalide ou expiré'], 400);
            return;
        }

        $user = $this->db()->fetch(
            "SELECT id, tenant_id, email, role FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL",
            [(int) $payload->sub]
        );

        if (!$user) {
            $this->json(['error' => 'invalid_user'], 400);
            return;
        }

        $this->issueTokens($user);
    }

    private function issueTokens(array $user): void
    {
        $accessPayload = JWTHandler::generate((int) $user['id'], (int) $user['tenant_id'], $user['role']);

        // Le refresh JWT (7 jours) — encodé manuellement pour type=refresh
        $now = time();
        $payload = [
            'iss' => 'saec.cloud',
            'sub' => (int) $user['id'],
            'tenant_id' => (int) $user['tenant_id'],
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + 604800,
            'jti' => bin2hex(random_bytes(16)),
        ];
        $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
        $payloadB64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "{$header}.{$payloadB64}", $GLOBALS['SAEC_CONFIG']['security']['jwt_secret'] ?? '', true)), '+/', '-_'), '=');
        $refreshToken = "{$header}.{$payloadB64}.{$signature}";

        $this->json([
            'access_token' => $accessPayload,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
            'tenant_id' => (string) $user['tenant_id'],
            'user_email' => $user['email'],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // DEVICE VERIFICATION (Web)
    // ═══════════════════════════════════════════════════════

    /**
     * GET /device — page d'approbation du device code
     */
    public function verifyForm(): void
    {
        $user = \Saec\Core\Session::get('user');

        $code = $_GET['code'] ?? '';
        if (!$user) {
            // Préserver le code après login
            $this->redirect('/login?redirect=' . urlencode('/device?code=' . urlencode($code)));
            return;
        }

        $db = $this->db();

        $pending = null;
        if ($code) {
            $pending = $db->fetch(
                "SELECT id, user_code, status FROM device_codes WHERE user_code = ? AND expires_at > NOW()",
                [strtoupper(trim($code))]
            );
        }

        $data = [
            'user' => $user,
            'pending' => $pending,
            'code' => $code,
            'pageTitle' => 'Autoriser le sync',
            'currentPage' => 'dashboard',
        ];
        $this->view('sync/verify', $data);
    }

    /**
     * POST /device — approuver un device code
     */
    public function verify(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $token = $_POST['_token'] ?? '';
        if (!\Saec\Core\Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (empty($code)) {
            $this->json(['error' => 'Code requis'], 400);
            return;
        }

        $db = $this->db();
        $row = $db->fetch(
            "SELECT id FROM device_codes WHERE user_code = ? AND status = 'pending' AND expires_at > NOW()",
            [$code]
        );

        if (!$row) {
            $this->json(['error' => 'Code invalide ou expiré'], 400);
            return;
        }

        $db->execute(
            "UPDATE device_codes SET status = 'approved', user_id = ?, tenant_id = ?, approved_at = NOW() WHERE id = ?",
            [$user['id'], $user['tenant_id'], $row['id']]
        );

        try {
            $db->insert('audit_logs', [
                'tenant_id' => $user['tenant_id'],
                'user_id' => $user['id'],
                'action' => 'sync.device_approved',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'metadata' => json_encode(['device_code_id' => $row['id']]),
            ]);
        } catch (\Throwable $e) {}

        $this->json(['success' => true, 'message' => 'Appareil autorisé. Le client sync peut maintenant se connecter.']);
    }

    // ═══════════════════════════════════════════════════════
    // SYNC API
    // ═══════════════════════════════════════════════════════

    private function requireApiUser(): array
    {
        $user = $_SERVER['SAEC_API_USER'] ?? null;
        if (!$user) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }
        return $user;
    }

    /**
     * GET /api/sync/mounts — mounts actifs du tenant
     */
    public function mounts(): void
    {
        $user = $this->requireApiUser();
        $mounts = MountService::getInstance()->listMounts((int) $user['tenant_id']);

        $result = [];
        foreach ($mounts as $m) {
            if (empty($m['is_active'])) continue;
            $result[] = [
                'id' => (string) $m['id'],
                'name' => $m['local_alias'],
                'provider_type' => $m['provider_type'] ?? $m['provider_name'],
                'remote_path' => $m['remote_path'],
                'local_alias' => $m['local_alias'],
                'mount_type' => $m['mount_type'],
                'sync_enabled' => (bool) ($m['sync_enabled'] ?? 0),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * GET /api/sync/mounts/{id}/files{path} — lister un dossier
     */
    public function files(string $id, ?string $path = null): void
    {
        $user = $this->requireApiUser();
        $mount = $this->getMountForTenant((int) $id, (int) $user['tenant_id']);
        if (!$mount) {
            $this->json(['error' => 'Mount non trouvé'], 404);
            return;
        }

        $relPath = $this->normalizePath($path ?? '');

        $rows = $this->db()->fetchAll(
            "SELECT id, path, size, checksum, mime_type, is_dir, updated_at
             FROM sync_files
             WHERE tenant_id = ? AND mount_id = ? AND parent_path " . ($relPath === '' ? "IS NULL" : "= ?") . "
             ORDER BY is_dir DESC, path ASC",
            $relPath === '' ? [(int) $user['tenant_id'], (int) $id] : [(int) $user['tenant_id'], (int) $id, $relPath]
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->fileInfo($row);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'items' => $items,
            'has_more' => false,
            'next_cursor' => null,
        ]);
    }

    /**
     * GET /api/sync/mounts/{id}/files/{path}/content — télécharger un fichier déchiffré
     */
    public function content(string $id, string $path): void
    {
        $user = $this->requireApiUser();
        $mount = $this->getMountForTenant((int) $id, (int) $user['tenant_id']);
        if (!$mount) {
            $this->json(['error' => 'Mount non trouvé'], 404);
            return;
        }

        $relPath = $this->normalizePath($path);
        $row = $this->db()->fetch(
            "SELECT * FROM sync_files WHERE tenant_id = ? AND mount_id = ? AND path = ? AND is_dir = 0",
            [(int) $user['tenant_id'], (int) $id, $relPath]
        );

        if (!$row || empty($row['file_key'])) {
            $this->json(['error' => 'Fichier non trouvé'], 404);
            return;
        }

        try {
            $adapter = StorageService::getInstance()->getAdapter((int) $mount['provider_id']);
            $fullPath = $this->remoteFullPath($mount, $relPath);
            $encrypted = $adapter->read($fullPath);

            $content = (new Encryption())->decryptContent($encrypted, $row['file_key']);
        } catch (\Throwable $e) {
            error_log("[SyncAPI] Content read failed: {$e->getMessage()}");
            $this->json(['error' => 'Lecture impossible'], 500);
            return;
        }

        header('Content-Type: application/octet-stream');
        header('X-File-Checksum: ' . $row['checksum']);
        header('Content-Length: ' . strlen($content));
        echo $content;
    }

    /**
     * PUT /api/sync/mounts/{id}/files{path} — uploader (chiffré avant stockage)
     */
    public function upload(string $id, ?string $path = null): void
    {
        $user = $this->requireApiUser();
        $mount = $this->getMountForTenant((int) $id, (int) $user['tenant_id']);
        if (!$mount) {
            $this->json(['error' => 'Mount non trouvé'], 404);
            return;
        }
        if ($mount['mount_type'] === 'readonly') {
            $this->json(['error' => 'Mount en lecture seule'], 403);
            return;
        }

        $relPath = $this->normalizePath($path ?? '');
        if ($relPath === '') {
            $this->json(['error' => 'Chemin requis'], 400);
            return;
        }

        $content = file_get_contents('php://input');
        if ($content === false) {
            $this->json(['error' => 'Corps de requête illisible'], 400);
            return;
        }

        // Le client envoie X-File-Checksum (blake3). On le reflète pour garder
        // l'index du client cohérent (sinon conflits permanents local vs remote).
        $checksum = $_SERVER['HTTP_X_FILE_CHECKSUM'] ?? '';
        if (empty($checksum) || !preg_match('/^[a-f0-9]{1,128}$/i', $checksum)) {
            $checksum = hash('sha256', $content);
        }
        $mime = $this->guessMime(basename($relPath));
        $db = $this->db();

        try {
            // Assurer les dossiers parents
            $parent = $this->ensureParentFolders($mount, $user, $relPath);

            // Chiffrer
            $encryption = new Encryption();
            $key = $encryption->generateFileKey();
            $iv = random_bytes(12);
            $tag = '';
            $ciphertext = openssl_encrypt($content, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($ciphertext === false) {
                throw new \RuntimeException('Encryption failed');
            }
            $blob = $iv . $tag . $ciphertext;

            // Écrire sur le provider distant
            $adapter = StorageService::getInstance()->getAdapter((int) $mount['provider_id']);
            $fullPath = $this->remoteFullPath($mount, $relPath);
            if (!$adapter->write($fullPath, $blob, ['original_name' => basename($relPath)])) {
                throw new \RuntimeException('Provider write failed');
            }

            // Indexer en base
            $existing = $db->fetch(
                "SELECT id FROM sync_files WHERE tenant_id = ? AND mount_id = ? AND path = ?",
                [(int) $user['tenant_id'], (int) $id, $relPath]
            );

            if ($existing) {
                $db->execute(
                    "UPDATE sync_files SET size = ?, checksum = ?, mime_type = ?, file_key = ?, updated_at = NOW() WHERE id = ?",
                    [strlen($content), $checksum, $mime, base64_encode($key), $existing['id']]
                );
                $fileId = (int) $existing['id'];
            } else {
                $fileId = $db->insert('sync_files', [
                    'tenant_id' => (int) $user['tenant_id'],
                    'mount_id' => (int) $id,
                    'user_id' => (int) $user['id'],
                    'path' => $relPath,
                    'name' => basename($relPath),
                    'parent_path' => $parent,
                    'is_dir' => 0,
                    'size' => strlen($content),
                    'checksum' => $checksum,
                    'mime_type' => $mime,
                    'file_key' => base64_encode($key),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->json([
                'success' => true,
                'file' => $this->fileInfo([
                    'id' => $fileId,
                    'path' => $relPath,
                    'size' => strlen($content),
                    'checksum' => $checksum,
                    'mime_type' => $mime,
                    'is_dir' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]),
            ]);
        } catch (\Throwable $e) {
            error_log("[SyncAPI] Upload failed: {$e->getMessage()}");
            $this->json(['error' => 'Upload échoué: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/sync/mounts/{id}/files{path} — supprimer un fichier
     */
    public function delete(string $id, ?string $path = null): void
    {
        $user = $this->requireApiUser();
        $mount = $this->getMountForTenant((int) $id, (int) $user['tenant_id']);
        if (!$mount) {
            $this->json(['error' => 'Mount non trouvé'], 404);
            return;
        }
        if ($mount['mount_type'] === 'readonly') {
            $this->json(['error' => 'Mount en lecture seule'], 403);
            return;
        }

        $relPath = $this->normalizePath($path ?? '');
        if ($relPath === '') {
            $this->json(['error' => 'Chemin requis'], 400);
            return;
        }

        $db = $this->db();
        $row = $db->fetch(
            "SELECT id FROM sync_files WHERE tenant_id = ? AND mount_id = ? AND path = ?",
            [(int) $user['tenant_id'], (int) $id, $relPath]
        );

        if (!$row) {
            $this->json(['success' => true]); // idempotent
            return;
        }

        try {
            $adapter = StorageService::getInstance()->getAdapter((int) $mount['provider_id']);
            $adapter->delete($this->remoteFullPath($mount, $relPath));
        } catch (\Throwable $e) {
            error_log("[SyncAPI] Remote delete failed: {$e->getMessage()}");
        }

        $db->execute(
            "DELETE FROM sync_files WHERE tenant_id = ? AND mount_id = ? AND path = ?",
            [(int) $user['tenant_id'], (int) $id, $relPath]
        );

        $this->json(['success' => true]);
    }

    /**
     * POST /api/sync/mounts/{id}/files{path} — créer un dossier
     */
    public function createFolder(string $id, ?string $path = null): void
    {
        $user = $this->requireApiUser();
        $mount = $this->getMountForTenant((int) $id, (int) $user['tenant_id']);
        if (!$mount) {
            $this->json(['error' => 'Mount non trouvé'], 404);
            return;
        }
        if ($mount['mount_type'] === 'readonly') {
            $this->json(['error' => 'Mount en lecture seule'], 403);
            return;
        }

        $relPath = $this->normalizePath($path ?? '');
        if ($relPath === '') {
            $this->json(['error' => 'Chemin requis'], 400);
            return;
        }

        $db = $this->db();
        $parent = $this->normalizePath(dirname($relPath));

        $existing = $db->fetch(
            "SELECT id FROM sync_files WHERE tenant_id = ? AND mount_id = ? AND path = ? AND is_dir = 1",
            [(int) $user['tenant_id'], (int) $id, $relPath]
        );
        if ($existing) {
            $this->json(['success' => true]);
            return;
        }

        try {
            $adapter = StorageService::getInstance()->getAdapter((int) $mount['provider_id']);
            $adapter->mkdir($this->remoteFullPath($mount, $relPath));

            $folderId = $db->insert('sync_files', [
                'tenant_id' => (int) $user['tenant_id'],
                'mount_id' => (int) $id,
                'user_id' => (int) $user['id'],
                'path' => $relPath,
                'name' => basename($relPath),
                'parent_path' => $parent === '' ? null : $parent,
                'is_dir' => 1,
                'size' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->json([
                'success' => true,
                'file' => $this->fileInfo([
                    'id' => $folderId,
                    'path' => $relPath,
                    'size' => 0,
                    'checksum' => null,
                    'mime_type' => null,
                    'is_dir' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]),
            ]);
        } catch (\Throwable $e) {
            error_log("[SyncAPI] CreateFolder failed: {$e->getMessage()}");
            $this->json(['error' => 'Création dossier échouée'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    private function getMountForTenant(int $id, int $tenantId): ?array
    {
        $mount = MountService::getInstance()->getMount($id);
        if (!$mount || (int) $mount['tenant_id'] !== $tenantId || empty($mount['is_active'])) {
            return null;
        }
        return $mount;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') continue;
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }
        return implode('/', $parts);
    }

    private function remoteFullPath(array $mount, string $relPath): string
    {
        $base = rtrim($mount['remote_path'] ?? '/', '/');
        return $base . ($relPath !== '' ? '/' . $relPath : '');
    }

    private function ensureParentFolders(array $mount, array $user, string $relPath): ?string
    {
        $parent = $this->normalizePath(dirname($relPath));
        if ($parent === '') return null;

        $db = $this->db();
        $adapter = StorageService::getInstance()->getAdapter((int) $mount['provider_id']);

        $prev = '';
        foreach (explode('/', $parent) as $segment) {
            $prev = $prev === '' ? $segment : $prev . '/' . $segment;

            $exists = $db->fetch(
                "SELECT id FROM sync_files WHERE tenant_id = ? AND mount_id = ? AND path = ? AND is_dir = 1",
                [(int) $user['tenant_id'], (int) $mount['id'], $prev]
            );
            if ($exists) continue;

            try {
                $adapter->mkdir($this->remoteFullPath($mount, $prev));
            } catch (\Throwable $e) {
                // Le dossier peut déjà exister côté provider
            }

            $db->insert('sync_files', [
                'tenant_id' => (int) $user['tenant_id'],
                'mount_id' => (int) $mount['id'],
                'user_id' => (int) $user['id'],
                'path' => $prev,
                'name' => $segment,
                'parent_path' => $this->normalizePath(dirname($prev)) ?: null,
                'is_dir' => 1,
                'size' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $parent;
    }

    private function fileInfo(array $row): array
    {
        $path = $row['path'];
        $name = $row['name'] ?? basename($path);
        $modified = ($row['updated_at'] ?? null)
            ? date('c', strtotime($row['updated_at']))
            : date('c');

        return [
            'id' => (string) $row['id'],
            'name' => $name,
            'path' => $path,
            'is_dir' => (bool) ($row['is_dir'] ?? 0),
            'size' => (int) ($row['size'] ?? 0),
            'modified' => $modified,
            'checksum' => $row['checksum'] ?? null,
            'mime_type' => $row['mime_type'] ?? null,
        ];
    }

    private function guessMime(string $name): string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'webp' => 'image/webp', 'txt' => 'text/plain', 'html' => 'text/html',
            'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json',
            'csv' => 'text/csv', 'xml' => 'application/xml', 'zip' => 'application/zip',
            'mp4' => 'video/mp4', 'mp3' => 'audio/mpeg',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    private function generateUserCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $part1 = '';
        $part2 = '';
        for ($i = 0; $i < 4; $i++) {
            $part1 .= $chars[random_int(0, strlen($chars) - 1)];
            $part2 .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $part1 . '-' . $part2;
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'cloud.saec.me';
        return $scheme . '://' . $host;
    }
}