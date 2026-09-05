<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;

class FolderController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();
        $tenantId = $user['tenant_id'];

        $parentId = isset($_GET['folder']) ? (int) $_GET['folder'] : null;

        $currentFolder = null;
        if ($parentId) {
            $currentFolder = $db->fetch(
                "SELECT * FROM folders WHERE id = ? AND tenant_id = ?",
                [$parentId, $tenantId]
            );
            if (!$currentFolder) {
                $this->redirect('/files');
                return;
            }
        }

        // Sous-dossiers
        $folders = $db->fetchAll(
            "SELECT f.*, 
                    (SELECT COUNT(*) FROM files WHERE folder_id = f.id AND deleted_at IS NULL) as file_count,
                    (SELECT COUNT(*) FROM folders WHERE parent_id = f.id) as subfolder_count
             FROM folders f
             WHERE f.tenant_id = ? AND " . ($parentId ? "f.parent_id = ?" : "f.parent_id IS NULL") . "
             ORDER BY f.name ASC",
            $parentId ? [$tenantId, $parentId] : [$tenantId]
        );

        // Fichiers dans ce dossier
        $files = $db->fetchAll(
            "SELECT id, original_name, mime_type, size, created_at
             FROM files 
             WHERE tenant_id = ? AND " . ($parentId ? "folder_id = ?" : "folder_id IS NULL") . " AND deleted_at IS NULL
             ORDER BY original_name ASC",
            $parentId ? [$tenantId, $parentId] : [$tenantId]
        );

        // Breadcrumb
        $breadcrumb = $this->buildBreadcrumb($tenantId, $parentId);

        // Quota
        $usage = $db->fetch(
            "SELECT COALESCE(SUM(size), 0) as used FROM files WHERE tenant_id = ? AND deleted_at IS NULL",
            [$tenantId]
        );
        $tenant = $db->fetch(
            "SELECT storage_quota FROM tenants WHERE id = ?",
            [$tenantId]
        );

        $data = [
            'user' => $user,
            'folders' => $folders,
            'files' => $files,
            'current_folder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'parent_id' => $parentId,
            'used' => (int) ($usage['used'] ?? 0),
            'quota' => (int) ($tenant['storage_quota'] ?? 10737418240),
            'pageTitle' => $currentFolder ? $currentFolder['name'] : 'Fichiers',
        ];

        $this->view('files/index', $data);
    }

    public function create(): void
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

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->json(['error' => 'Nom du dossier requis'], 400);
            return;
        }

        if (strlen($name) > 255) {
            $this->json(['error' => 'Nom trop long (max 255 caractères)'], 400);
            return;
        }

        // Caractères interdits
        if (preg_match('/[\/\\x00-\x1f]/', $name)) {
            $this->json(['error' => 'Nom contient des caractères interdits'], 400);
            return;
        }

        $db = Database::getInstance();
        $tenantId = $user['tenant_id'];
        $parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

        // Vérifier doublon
        $existing = $db->fetch(
            "SELECT id FROM folders WHERE tenant_id = ? AND " . ($parentId ? "parent_id = ?" : "parent_id IS NULL") . " AND name = ?",
            $parentId ? [$tenantId, $parentId, $name] : [$tenantId, $name]
        );

        if ($existing) {
            $this->json(['error' => 'Un dossier avec ce nom existe déjà'], 409);
            return;
        }

        // Construire le path
        $path = '/';
        if ($parentId) {
            $parent = $db->fetch(
                "SELECT path FROM folders WHERE id = ? AND tenant_id = ?",
                [$parentId, $tenantId]
            );
            if ($parent) {
                $path = rtrim($parent['path'], '/') . '/' . $name;
            }
        } else {
            $path = '/' . $name;
        }

        $folderId = $db->insert('folders', [
            'tenant_id' => $tenantId,
            'user_id' => $user['id'],
            'parent_id' => $parentId,
            'name' => $name,
            'path' => $path,
        ]);

        // Créer le répertoire physique
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/' . $tenantId;
        $physicalPath = $uploadDir . '/' . ltrim($path, '/');
        if (!is_dir($physicalPath)) {
            mkdir($physicalPath, 0770, true);
        }

        // Audit log
        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $tenantId,
                    'user_id' => $user['id'],
                    'action' => 'folder.created',
                    'resource_type' => 'folder',
                    'resource_id' => $folderId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $this->json([
            'success' => true,
            'folder' => [
                'id' => $folderId,
                'name' => $name,
                'path' => $path,
            ],
        ]);
    }

    public function rename(string $id): void
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

        $db = Database::getInstance();
        $tenantId = $user['tenant_id'];

        $folder = $db->fetch(
            "SELECT * FROM folders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$folder) {
            $this->json(['error' => 'Dossier non trouvé'], 404);
            return;
        }

        $newName = trim($_POST['name'] ?? '');
        if (empty($newName)) {
            $this->json(['error' => 'Nom requis'], 400);
            return;
        }

        if (preg_match('/[\/\\x00-\x1f]/', $newName)) {
            $this->json(['error' => 'Nom contient des caractères interdits'], 400);
            return;
        }

        // Construire nouveau path
        $parentPath = '/';
        if ($folder['parent_id']) {
            $parent = $db->fetch(
                "SELECT path FROM folders WHERE id = ?",
                [$folder['parent_id']]
            );
            $parentPath = $parent['path'] ?? '/';
        }
        $newPath = rtrim($parentPath, '/') . '/' . $newName;

        // Mettre à jour récursivement les paths enfants
        $oldPathPrefix = rtrim($folder['path'], '/');
        $newPathPrefix = rtrim($newPath, '/');

        $db->execute(
            "UPDATE folders SET path = REPLACE(path, ?, ?) WHERE path LIKE ? AND tenant_id = ?",
            [$oldPathPrefix, $newPathPrefix, $oldPathPrefix . '/%', $tenantId]
        );

        $db->execute(
            "UPDATE folders SET name = ?, path = ? WHERE id = ?",
            [$newName, $newPath, $id]
        );

        // Audit log
        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $tenantId,
                    'user_id' => $user['id'],
                    'action' => 'folder.renamed',
                    'resource_type' => 'folder',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'metadata' => json_encode(['old_name' => $folder['name'], 'new_name' => $newName]),
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true]);
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth();

        // For DELETE requests, CSRF token may be in body or header
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$token && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            // Try to read from php://input
            $raw = file_get_contents('php://input');
            parse_str($raw, $body);
            $token = $body['_token'] ?? '';
        }
        if (!$token) {
            $token = $_POST['_token'] ?? '';
        }
        if (!\Saec\Core\Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $db = Database::getInstance();
        $tenantId = $user['tenant_id'];

        $folder = $db->fetch(
            "SELECT * FROM folders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$folder) {
            $this->json(['error' => 'Dossier non trouvé'], 404);
            return;
        }

        // Vérifier sous-dossiers
        $subfolders = $db->fetch(
            "SELECT COUNT(*) as count FROM folders WHERE parent_id = ?",
            [$id]
        );
        if ($subfolders['count'] > 0) {
            $this->json(['error' => 'Dossier non vide (sous-dossiers)'], 400);
            return;
        }

        // Vérifier fichiers
        $filesCount = $db->fetch(
            "SELECT COUNT(*) as count FROM files WHERE folder_id = ? AND deleted_at IS NULL",
            [$id]
        );
        if ($filesCount['count'] > 0) {
            $this->json(['error' => 'Dossier non vide (fichiers)'], 400);
            return;
        }

        $db->execute("DELETE FROM folders WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $tenantId,
                    'user_id' => $user['id'],
                    'action' => 'folder.deleted',
                    'resource_type' => 'folder',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true]);
    }

    private function buildBreadcrumb(int $tenantId, ?int $folderId): array
    {
        if (!$folderId) {
            return [['name' => 'Fichiers', 'url' => '/files']];
        }

        $db = Database::getInstance();
        $breadcrumb = [];
        $current = $db->fetch(
            "SELECT id, name, parent_id FROM folders WHERE id = ? AND tenant_id = ?",
            [$folderId, $tenantId]
        );

        while ($current) {
            array_unshift($breadcrumb, [
                'name' => $current['name'],
                'url' => '/files?folder=' . $current['id'],
            ]);
            if ($current['parent_id']) {
                $current = $db->fetch(
                    "SELECT id, name, parent_id FROM folders WHERE id = ? AND tenant_id = ?",
                    [$current['parent_id'], $tenantId]
                );
            } else {
                $current = null;
            }
        }

        array_unshift($breadcrumb, ['name' => 'Fichiers', 'url' => '/files']);
        return $breadcrumb;
    }
}
