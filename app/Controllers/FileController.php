<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Encryption;

class FileController extends Controller
{
    private function getUploadDir(int $tenantId): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/uploads/' . $tenantId;
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        return $dir;
    }

    public function index(): void
    {
        $user = $this->requireAuth();
        $this->redirect('/files');
    }

    public function upload(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Upload failed: ' . ($file['error'] ?? 'no file')], 400);
            return;
        }

        $config = $GLOBALS['SAEC_CONFIG']['upload'] ?? [];
        $maxSize = $config['max_file_size'] ?? 104857600;

        if ($file['size'] > $maxSize) {
            $this->json(['error' => 'Fichier trop volumineux (max ' . number_format($maxSize / 1048576) . 'MB)'], 400);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowed = $config['allowed_types'] ?? [];
        if (!empty($allowed) && !in_array($mimeType, $allowed)) {
            $this->json(['error' => "Type '{$mimeType}' non autorisé"], 400);
            return;
        }

        $db = Database::getInstance();
        $tenantId = $user['tenant_id'];
        $userId = $user['id'];

        // Quota check
        $tenant = $db->fetch(
            "SELECT storage_quota FROM tenants WHERE id = ?",
            [$tenantId]
        );
        $currentUsage = $db->fetch(
            "SELECT COALESCE(SUM(size), 0) as used FROM files WHERE tenant_id = ? AND deleted_at IS NULL",
            [$tenantId]
        );
        if ($currentUsage['used'] + $file['size'] > $tenant['storage_quota']) {
            $this->json(['error' => 'Quota de stockage dépassé'], 400);
            return;
        }

        // Folder check
        $folderId = isset($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;
        if ($folderId) {
            $folder = $db->fetch(
                "SELECT id FROM folders WHERE id = ? AND tenant_id = ?",
                [$folderId, $tenantId]
            );
            if (!$folder) {
                $this->json(['error' => 'Dossier non trouvé'], 400);
                return;
            }
        }

        // Nom sécurisé
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedName = bin2hex(random_bytes(16)) . ($ext ? ".{$ext}" : '');

        // Isolation: storage/uploads/{tenant_id}/
        $uploadDir = $this->getUploadDir($tenantId);
        $storagePath = $uploadDir . '/' . $storedName;

        // Chiffrement AES-256-GCM
        $encryption = new Encryption();
        $result = $encryption->encryptFile($file['tmp_name'], $storagePath);

        // Checksum du fichier chiffré
        $checksum = hash_file('sha256', $storagePath);

        $fileId = $db->insert('files', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'folder_id' => $folderId,
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'checksum' => $checksum,
            'file_key' => $result['key'],
        ]);

        $db->insert('audit_logs', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => 'file.uploaded',
            'resource_type' => 'file',
            'resource_id' => $fileId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'metadata' => json_encode([
                'name' => $file['name'],
                'size' => $file['size'],
                'mime' => $mimeType,
                'folder_id' => $folderId,
            ]),
        ]);

        $this->json([
            'success' => true,
            'file' => [
                'id' => $fileId,
                'name' => $file['name'],
                'size' => $file['size'],
                'mime_type' => $mimeType,
            ],
        ]);
    }

    public function download(string $id): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file) {
            http_response_code(404);
            echo "Fichier non trouvé";
            return;
        }

        if (empty($file['file_key'])) {
            http_response_code(500);
            echo "Clé de chiffrement manquante";
            return;
        }

        // Isolation: storage/uploads/{tenant_id}/
        $uploadDir = $this->getUploadDir($user['tenant_id']);
        $storagePath = $uploadDir . '/' . $file['stored_name'];

        if (!file_exists($storagePath)) {
            http_response_code(404);
            echo "Fichier manquant sur le serveur";
            return;
        }

        // Vérifier checksum
        $currentChecksum = hash_file('sha256', $storagePath);
        if ($currentChecksum !== $file['checksum']) {
            http_response_code(500);
            echo "Intégrité du fichier compromise";
            return;
        }

        $encryption = new Encryption();
        $content = $encryption->decryptFile($storagePath, $file['file_key']);

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'file.downloaded',
            'resource_type' => 'file',
            'resource_id' => $id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($file['original_name']) . '"');
        header('Content-Length: ' . strlen($content));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo $content;
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file) {
            $this->json(['error' => 'Fichier non trouvé'], 404);
            return;
        }

        $db->execute(
            "UPDATE files SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?",
            [$id, $user['tenant_id']]
        );

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'file.deleted',
            'resource_type' => 'file',
            'resource_id' => $id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $this->json(['success' => true]);
    }

    public function restore(string $id): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NOT NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file) {
            $this->json(['error' => 'Fichier non trouvé dans la corbeille'], 404);
            return;
        }

        $db->execute(
            "UPDATE files SET deleted_at = NULL WHERE id = ? AND tenant_id = ?",
            [$id, $user['tenant_id']]
        );

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'file.restored',
            'resource_type' => 'file',
            'resource_id' => $id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $this->json(['success' => true]);
    }

    public function trash(): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $files = $db->fetchAll(
            "SELECT id, original_name, mime_type, size, deleted_at
             FROM files 
             WHERE tenant_id = ? AND deleted_at IS NOT NULL
             ORDER BY deleted_at DESC",
            [$user['tenant_id']]
        );

        $data = [
            'user' => $user,
            'files' => $files,
            'folders' => [],
            'current_folder' => null,
            'breadcrumb' => [
                ['name' => 'Fichiers', 'url' => '/files'],
                ['name' => 'Corbeille', 'url' => '/trash'],
            ],
            'parent_id' => null,
            'used' => 0,
            'quota' => 0,
            'pageTitle' => 'Corbeille',
        ];

        $this->view('files/index', $data);
    }

    public function preview(string $id): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file || empty($file['file_key'])) {
            http_response_code(404);
            echo "Fichier non trouvé";
            return;
        }

        $uploadDir = $this->getUploadDir($user['tenant_id']);
        $storagePath = $uploadDir . '/' . $file['stored_name'];

        if (!file_exists($storagePath)) {
            http_response_code(404);
            echo "Fichier manquant";
            return;
        }

        $encryption = new Encryption();
        $content = $encryption->decryptFile($storagePath, $file['file_key']);

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . strlen($content));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo $content;
    }
}
