<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Encryption;
use Saec\Services\MountService;
use Saec\Services\StorageService;

class FileController extends Controller
{
    private function getUploadDir(int $tenantId): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/uploads/' . $tenantId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }
        if (!is_dir($dir)) {
            throw new \RuntimeException('Dossier upload non créable: ' . $dir);
        }
        return $dir;
    }

    /**
     * Construire le chemin relatif d'un dossier
     */
    private function getFolderPath(int $tenantId, ?int $folderId): string
    {
        if (!$folderId) return '/';
        
        $db = Database::getInstance();
        $folder = $db->fetch(
            "SELECT path FROM folders WHERE id = ? AND tenant_id = ?",
            [$folderId, $tenantId]
        );
        return $folder['path'] ?? '/';
    }

    /**
     * Assurer qu'un chemin de dossiers existe (créer récursivement)
     * Retourne l'ID du dossier final
     */
    private function ensureFolderPath(int $tenantId, int $userId, string $relativePath, ?int $baseFolderId = null): int
    {
        $db = Database::getInstance();
        
        // Normaliser le chemin
        $parts = array_filter(explode('/', trim($relativePath, '/')));
        if (empty($parts)) {
            return $baseFolderId ?? 0;
        }
        
        $currentParentId = $baseFolderId;
        $currentPath = $baseFolderId ? $this->getFolderPath($tenantId, $baseFolderId) : '/';
        
        foreach ($parts as $part) {
            $currentPath = rtrim($currentPath, '/') . '/' . $part;
            
            // Vérifier si le dossier existe déjà
            $existing = $db->fetch(
                "SELECT id FROM folders WHERE tenant_id = ? AND parent_id " . ($currentParentId ? "= ?" : "IS NULL") . " AND name = ?",
                array_merge([$tenantId], $currentParentId ? [$currentParentId] : [], [$part])
            );
            
            if ($existing) {
                $currentParentId = $existing['id'];
                continue;
            }
            
            // Créer le dossier
            $folderId = $db->insert('folders', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'parent_id' => $currentParentId,
                'name' => $part,
                'path' => $currentPath,
            ]);
            
            if (!$folderId) {
                throw new \RuntimeException("Échec création dossier: {$part}");
            }
            
            // Créer le répertoire physique
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/' . $tenantId;
            $physicalPath = $uploadDir . '/' . ltrim($currentPath, '/');
            if (!is_dir($physicalPath)) {
                mkdir($physicalPath, 0777, true);
                @chmod($physicalPath, 0777);
            }
            
            // Push vers mounts distants
            $mountService = MountService::getInstance();
            $mounts = $mountService->listMounts($tenantId);
            foreach ($mounts as $mount) {
                if (!in_array($mount['mount_type'], ['readwrite', 'backup_only'])) continue;
                if (empty($mount['is_active'])) continue;
                $remoteBase = rtrim($mount['remote_path'], '/');
                $folderRel = ltrim($currentPath, '/');
                $remotePath = $remoteBase . ($folderRel ? '/' . $folderRel : '');
                try {
                    $adapter = StorageService::getInstance()->getAdapter($mount['provider_id']);
                    $adapter->mkdir($remotePath);
                } catch (\Throwable $e) {
                    error_log("[SYNC] Mkdir on mount {$mount['id']} failed: " . $e->getMessage());
                }
            }
            
            // Audit log
            try {
                $db->insert('audit_logs', [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'action' => 'folder.created',
                    'resource_type' => 'folder',
                    'resource_id' => $folderId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}
            
            $currentParentId = $folderId;
        }
        
        return $currentParentId;
    }

    /**
     * Pousser la création d'un dossier vers les mounts distants
     * Le mount.remote_path est le chemin COMPLET sur le remote (ex: /cloud/tenant_1/subfolder)
     * On ne push que si folderPath est sous le chemin du mount
     */
    private function pushFolderToRemoteMounts(int $tenantId, string $folderPath): void
    {
        try {
            $mountService = MountService::getInstance();
            $mounts = $mountService->listMounts($tenantId);
            
            foreach ($mounts as $mount) {
                if (!in_array($mount['mount_type'], ['readwrite', 'backup_only'])) continue;
                if (empty($mount['is_active'])) continue;
                
                $mountRemotePath = rtrim($mount['remote_path'], '/');
                $tenantRoot = '/cloud/tenant_' . $tenantId;
                
                // Le mount doit être sous /cloud/tenant_{id}/
                if (!str_starts_with($mountRemotePath, rtrim($tenantRoot, '/'))) {
                    continue; // Mount n'appartient pas à ce tenant
                }
                
                // Calculer le chemin relatif du mount par rapport à la racine tenant
                $mountRelPath = ltrim(substr($mountRemotePath, strlen($tenantRoot)), '/');
                
                // Le folderPath doit commencer par mountRelPath (ou être le parent)
                $folderRelPath = ltrim($folderPath, '/');
                
                if (!empty($mountRelPath)) {
                    // Mount pointe vers un sous-dossier spécifique
                    if (!str_starts_with($folderRelPath, $mountRelPath)) {
                        continue; // Ce dossier n'est pas sous ce mount
                    }
                    // Chemin relatif au mount
                    $remoteSubPath = substr($folderRelPath, strlen($mountRelPath));
                    $remoteSubPath = ltrim($remoteSubPath, '/');
                    $remotePath = $mountRemotePath . ($remoteSubPath ? '/' . $remoteSubPath : '');
                } else {
                    // Mount pointe à la racine du tenant
                    $remotePath = $mountRemotePath . ($folderRelPath ? '/' . $folderRelPath : '');
                }
                
                try {
                    $adapter = StorageService::getInstance()->getAdapter($mount['provider_id']);
                    $adapter->mkdir($remotePath);
                    error_log("[SYNC] Created folder at mount {$mount['id']}: $remotePath");
                } catch (\Throwable $e) {
                    error_log("[SYNC] Mkdir on mount {$mount['id']} failed: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            error_log("[SYNC] Remote folder push error: " . $e->getMessage());
        }
    }

/**
     * Pousser un fichier vers les mounts distants du tenant (si readwrite/backup)
     */
    private function pushToRemoteMounts(int $tenantId, string $storedName, int $fileId, string $originalName, string $folderPath): void
    {
        try {
            $mountService = MountService::getInstance();
            $mounts = $mountService->listMounts($tenantId);
            
            foreach ($mounts as $mount) {
                // Ne pousser que sur les mounts readwrite ou backup_only
                if (!in_array($mount['mount_type'], ['readwrite', 'backup_only'])) continue;
                if (empty($mount['is_active'])) continue;
                
                $mountRemotePath = rtrim($mount['remote_path'], '/');
                $tenantRoot = '/cloud/tenant_' . $tenantId;
                
                // Le mount doit être sous /cloud/tenant_{id}/
                if (!str_starts_with($mountRemotePath, rtrim($tenantRoot, '/'))) {
                    continue; // Mount n'appartient pas à ce tenant
                }
                
                // Calculer le chemin relatif du mount par rapport à la racine tenant
                $mountRelPath = ltrim(substr($mountRemotePath, strlen($tenantRoot)), '/');
                
                // Le folderPath doit commencer par mountRelPath (ou être le parent)
                $folderRelPath = ltrim($folderPath, '/');
                
                if (!empty($mountRelPath)) {
                    // Mount pointe vers un sous-dossier spécifique
                    if (!str_starts_with($folderRelPath, $mountRelPath)) {
                        continue; // Ce dossier n'est pas sous ce mount
                    }
                    // Chemin relatif au mount
                    $remoteSubPath = substr($folderRelPath, strlen($mountRelPath));
                    $remoteSubPath = ltrim($remoteSubPath, '/');
                    $remotePath = $mountRemotePath . ($remoteSubPath ? '/' . $remoteSubPath : '') . '/' . $storedName;
                } else {
                    // Mount pointe à la racine du tenant
                    $remotePath = $mountRemotePath . ($folderRelPath ? '/' . $folderRelPath : '') . '/' . $storedName;
                }
                
                // Upload fichier chiffré vers le mount
                try {
                    $adapter = StorageService::getInstance()->getAdapter($mount['provider_id']);
                    $content = file_get_contents($this->getUploadDir($tenantId) . '/' . $storedName);
                    if ($content !== false) {
                        $adapter->write($remotePath, $content, [
                            'original_name' => $originalName,
                            'file_id' => $fileId,
                        ]);
                        error_log("[SYNC] Pushed file $fileId to mount {$mount['id']} at $remotePath");
                    }
                } catch (\Throwable $e) {
                    error_log("[SYNC] Push to mount {$mount['id']} failed: " . $e->getMessage());
                    // Ne pas faire échouer l'upload local si le push distant échoue
                }
            }
        } catch (\Throwable $e) {
            error_log("[SYNC] Remote push error: " . $e->getMessage());
        }
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

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? '');
        if (!\Saec\Core\Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
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

        // Support pour relative_path (drag & drop dossiers)
        $relativePath = isset($_POST['relative_path']) ? trim($_POST['relative_path']) : '';
        if ($relativePath) {
            // Extraire le dossier parent du chemin relatif
            $parentDir = dirname($relativePath);
            if ($parentDir !== '.' && $parentDir !== '/') {
                // Créer ou trouver la structure de dossiers
                $folderId = $this->ensureFolderPath($tenantId, $userId, $parentDir, $folderId);
            }
        }

        // Nom sécurisé
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedName = bin2hex(random_bytes(16)) . ($ext ? ".{$ext}" : '');

        // Isolation: storage/uploads/{tenant_id}/
        $uploadDir = $this->getUploadDir($tenantId);
        $storagePath = $uploadDir . '/' . $storedName;

        try {
            // OCTOPUS MODE: Déterminer le stockage cible
            $mountService = MountService::getInstance();
            $mounts = $mountService->listMounts($tenantId);
            $activeMounts = array_filter($mounts, fn($m) => !empty($m['is_active']) && in_array($m['mount_type'], ['readwrite', 'backup_only']));
            
            $storagePath = null;
            $remoteResults = [];
            $primaryMount = null;
            
            if (!empty($activeMounts)) {
                // OCTOPUS MODE: Upload DIRECT vers le remote (pas de local-first)
                // Sélectionner le mount primaire (premier readwrite actif)
                $primaryMount = array_values($activeMounts)[0];
                
                // Construire le chemin distant
                $folderPath = $this->getFolderPath($tenantId, $folderId);
                $remoteBase = rtrim($primaryMount['remote_path'], '/');
                $folderRel = ltrim($folderPath, '/');
                $remotePath = $remoteBase . ($folderRel ? '/' . $folderRel : '') . '/' . $storedName;
                
                // Chiffrer le fichier temporairement
                $tempPath = sys_get_temp_dir() . '/' . $storedName;
                $encryption = new Encryption();
                $result = $encryption->encryptFile($file['tmp_name'], $tempPath);
                $checksum = hash_file('sha256', $tempPath);
                
                if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                    throw new \RuntimeException("Échec chiffrement fichier");
                }
                
                // Upload DIRECT vers le provider distant
                try {
                    $adapter = StorageService::getInstance()->getAdapter($primaryMount['provider_id']);
                    $content = file_get_contents($tempPath);
                    if ($content === false) {
                        throw new \RuntimeException("Impossible de lire le fichier chiffré");
                    }
                    
                    $success = $adapter->write($remotePath, $content, [
                        'original_name' => $file['name'],
                        'file_id' => 'pending',
                    ]);
                    
                    if (!$success) {
                        throw new \RuntimeException("Échec upload vers provider distant");
                    }
                    
                    $remoteResults[] = [
                        'mount_id' => $primaryMount['id'],
                        'provider_id' => $primaryMount['provider_id'],
                        'remote_path' => $remotePath,
                        'success' => true,
                    ];
                    
                    error_log("[OCTOPUS] Upload direct vers mount {$primaryMount['id']} ({$primaryMount['provider_name']}): $remotePath");
                    
                } catch (\Throwable $e) {
                    error_log("[OCTOPUS] Upload distant échoué: " . $e->getMessage());
                    // Fallback: local storage
                    $storagePath = $this->getUploadDir($tenantId) . '/' . $storedName;
                    rename($tempPath, $storagePath);
                }
                
                // Nettoyer le fichier temp si upload distant réussi
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
                
            } else {
                // Pas de mount distant actif → stockage local uniquement
                $uploadDir = $this->getUploadDir($tenantId);
                $storagePath = $uploadDir . '/' . $storedName;
                
                $encryption = new Encryption();
                $result = $encryption->encryptFile($file['tmp_name'], $storagePath);
                $checksum = hash_file('sha256', $storagePath);
                
                if (!file_exists($storagePath) || filesize($storagePath) === 0) {
                    throw new \RuntimeException("Échec écriture fichier sur disque local");
                }
            }

            // Si pas de remoteResults, on est en mode local (storagePath défini)
            if (!isset($checksum)) {
                $checksum = hash_file('sha256', $storagePath);
            }

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
                'storage_location' => $primaryMount ? 'remote:' . $primaryMount['id'] : 'local',
            ]);

            if (!$fileId) {
                throw new \RuntimeException("Échec insertion en base de données");
            }

            try {
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
                        'storage' => $primaryMount ? 'remote' : 'local',
                        'mount_id' => $primaryMount['id'] ?? null,
                    ]),
                ]);
            } catch (\Throwable $e) {}

            // Si remote upload réussi, mettre à jour l'audit avec file_id réel
            if (!empty($remoteResults)) {
                foreach ($remoteResults as &$rr) {
                    $rr['file_id'] = $fileId;
                }
            }

            $this->json([
                'success' => true,
                'file' => [
                    'id' => $fileId,
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'mime_type' => $mimeType,
                    'folder_id' => $folderId,
                    'storage' => $primaryMount ? 'remote' : 'local',
                ],
            ]);
        } catch (\Throwable $e) {
            if (isset($storagePath) && file_exists($storagePath)) {
                @unlink($storagePath);
            }
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            $this->json(['error' => 'Erreur upload: ' . $e->getMessage()], 500);
        }
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

        $content = null;
        
        // OCTOPUS MODE: Si stockage distant, lire depuis le provider
        if (!empty($file['storage_location']) && str_starts_with($file['storage_location'], 'remote:')) {
            $mountId = (int) substr($file['storage_location'], 7);
            $mountService = MountService::getInstance();
            $mount = $mountService->getMount($mountId);
            
            if ($mount) {
                try {
                    $adapter = StorageService::getInstance()->getAdapter($mount['provider_id']);
                    $folderPath = $this->getFolderPath($user['tenant_id'], $file['folder_id']);
                    $remoteBase = rtrim($mount['remote_path'], '/');
                    $folderRel = ltrim($folderPath, '/');
                    $remotePath = $remoteBase . ($folderRel ? '/' . $folderRel : '') . '/' . $file['stored_name'];
                    
                    $content = $adapter->read($remotePath);
                    error_log("[OCTOPUS] Download from remote mount {$mountId}: $remotePath");
                } catch (\Throwable $e) {
                    error_log("[OCTOPUS] Remote download failed, fallback local: " . $e->getMessage());
                }
            }
        }
        
        // Fallback: lecture locale
        if ($content === null) {
            // Isolation stricte: storage/uploads/{tenant_id}/
            $uploadDir = $this->getUploadDir($user['tenant_id']);
            $storagePath = $uploadDir . '/' . $file['stored_name'];

            // Vérifier que le path est bien dans le dossier du tenant (prevent path traversal)
            $realUploadDir = realpath($uploadDir);
            $realStoragePath = realpath($storagePath);
            if ($realUploadDir === false || $realStoragePath === false || !str_starts_with($realStoragePath, $realUploadDir . '/')) {
                http_response_code(403);
                echo "Accès interdit";
                return;
            }

            if (!file_exists($storagePath)) {
                http_response_code(404);
                echo "Fichier manquant sur le serveur";
                return;
            }

            $encryption = new Encryption();
            $content = $encryption->decryptFile($storagePath, $file['file_key']);
        } else {
            // Déchiffrer le contenu distant
            $encryption = new Encryption();
            $content = $encryption->decryptContent($content, $file['file_key']);
        }

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'file.downloaded',
                    'resource_type' => 'file',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

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

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'file.deleted',
                    'resource_type' => 'file',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

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

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'file.restored',
                    'resource_type' => 'file',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

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

        // Path traversal protection
        $realUploadDir = realpath($uploadDir);
        $realStoragePath = realpath($storagePath);
        if ($realUploadDir === false || $realStoragePath === false || !str_starts_with($realStoragePath, $realUploadDir . '/')) {
            http_response_code(403);
            echo "Accès interdit";
            return;
        }

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

    /**
     * Vue inline du fichier — affiche le contenu dans le SaaS
     */
    public function view(string $view, array $data = []): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();
        $id = $data['id'] ?? null;

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file) {
            http_response_code(404);
            echo "Fichier non trouvé";
            return;
        }

        $content = $this->encryption->decryptFile(
            $this->getUploadDir($user['tenant_id']) . '/' . $file['stored_name'],
            $file['file_key']
        );

        $this->view($view, array_merge($data, [
            'user' => $user,
            'file' => $file,
            'content' => $content,
            'pageTitle' => $file['original_name'],
        ]));
    }

    /**
     * API: retourne le contenu du fichier (JSON)
     */
    public function getContent(string $id): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file || empty($file['file_key'])) {
            $this->json(['error' => 'Fichier non trouvé'], 404);
            return;
        }

        $uploadDir = $this->getUploadDir($user['tenant_id']);
        $storagePath = $uploadDir . '/' . $file['stored_name'];

        $realUploadDir = realpath($uploadDir);
        $realStoragePath = realpath($storagePath);
        if ($realUploadDir === false || $realStoragePath === false || !str_starts_with($realStoragePath, $realUploadDir . '/')) {
            $this->json(['error' => 'Accès interdit'], 403);
            return;
        }

        if (!file_exists($storagePath)) {
            $this->json(['error' => 'Fichier manquant sur le serveur'], 404);
            return;
        }

        $encryption = new Encryption();
        $content = $encryption->decryptFile($storagePath, $file['file_key']);

        $mime = $file['mime_type'] ?? '';
        $isText = str_starts_with($mime, 'text/')
            || in_array($mime, ['application/json', 'application/javascript', 'application/xml', 'application/x-httpd-php']);

        $this->json([
            'success' => true,
            'file' => [
                'id' => $file['id'],
                'name' => $file['original_name'],
                'mime_type' => $mime,
                'size' => $file['size'],
                'is_text' => $isText,
                'content' => $isText ? $content : base64_encode($content),
            ],
        ]);
    }

    /**
     * Sauvegarder le contenu édité d'un fichier texte
     */
    public function saveContent(string $id): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $db = Database::getInstance();
        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file || empty($file['file_key'])) {
            $this->json(['error' => 'Fichier non trouvé'], 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $newContent = $body['content'] ?? null;

        if ($newContent === null) {
            $this->json(['error' => 'Contenu manquant'], 400);
            return;
        }

        $uploadDir = $this->getUploadDir($user['tenant_id']);
        $storagePath = $uploadDir . '/' . $file['stored_name'];

        try {
            $encryption = new Encryption();
            $result = $encryption->encryptFileFromContent($newContent, $storagePath);

            $checksum = hash_file('sha256', $storagePath);

            $db->execute(
                "UPDATE files SET checksum = ?, file_key = ?, size = ?, version = version + 1, updated_at = NOW() WHERE id = ? AND tenant_id = ?",
                [$checksum, $result['key'], strlen($newContent), $id, $user['tenant_id']]
            );

            // Sauvegarder la version
            $db->insert('file_versions', [
                'file_id' => $id,
                'version' => $file['version'] + 1,
                'stored_name' => $file['stored_name'],
                'size' => strlen($newContent),
                'checksum' => $checksum,
                'created_by' => $user['id'],
            ]);

            try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'file.edited',
                    'resource_type' => 'file',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

            $this->json(['success' => true, 'checksum' => $checksum]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Erreur sauvegarde: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Déplacer un fichier vers un dossier
     */
    public function move(string $id): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $db = Database::getInstance();
        $body = json_decode(file_get_contents('php://input'), true);
        $targetFolderId = isset($body['folder_id']) ? (int) $body['folder_id'] : null;

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file) {
            $this->json(['error' => 'Fichier non trouvé'], 404);
            return;
        }

        if ($targetFolderId) {
            $folder = $db->fetch(
                "SELECT id FROM folders WHERE id = ? AND tenant_id = ?",
                [$targetFolderId, $user['tenant_id']]
            );
            if (!$folder) {
                $this->json(['error' => 'Dossier destination non trouvé'], 404);
                return;
            }
        }

        $db->execute(
            "UPDATE files SET folder_id = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?",
            [$targetFolderId, $id, $user['tenant_id']]
        );

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'file.moved',
                    'resource_type' => 'file',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'metadata' => json_encode(['target_folder_id' => $targetFolderId]),
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true]);
    }

    /**
     * Copier un fichier vers un dossier
     */
    public function copy(string $id): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? '');
        if (!\Saec\Core\Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $db = Database::getInstance();
        $body = json_decode(file_get_contents('php://input'), true);
        $targetFolderId = isset($body['folder_id']) ? (int) $body['folder_id'] : null;

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$id, $user['tenant_id']]
        );

        if (!$file) {
            $this->json(['error' => 'Fichier non trouvé'], 404);
            return;
        }

        if ($targetFolderId) {
            $folder = $db->fetch(
                "SELECT id FROM folders WHERE id = ? AND tenant_id = ?",
                [$targetFolderId, $user['tenant_id']]
            );
            if (!$folder) {
                $this->json(['error' => 'Dossier destination non trouvé'], 404);
                return;
            }
        }

        // Générer nouveau nom stocké
        $ext = pathinfo($file['stored_name'], PATHINFO_EXTENSION);
        $newStoredName = bin2hex(random_bytes(16)) . ($ext ? ".{$ext}" : '');

        // Copier le fichier physiquement (local)
        $uploadDir = $this->getUploadDir($user['tenant_id']);
        $srcPath = $uploadDir . '/' . $file['stored_name'];
        $dstPath = $uploadDir . '/' . $newStoredName;

        if (!copy($srcPath, $dstPath)) {
            $this->json(['error' => 'Échec copie fichier'], 500);
            return;
        }

        // Si stockage distant, copier aussi là-bas
        if (!empty($file['storage_location']) && str_starts_with($file['storage_location'], 'remote:')) {
            $mountId = (int) substr($file['storage_location'], 7);
            $mountService = MountService::getInstance();
            $mount = $mountService->getMount($mountId);
            
            if ($mount) {
                try {
                    $adapter = StorageService::getInstance()->getAdapter($mount['provider_id']);
                    $folderPath = $this->getFolderPath($user['tenant_id'], $targetFolderId ?? $file['folder_id']);
                    $remoteBase = rtrim($mount['remote_path'], '/');
                    $folderRel = ltrim($folderPath, '/');
                    $remotePath = $remoteBase . ($folderRel ? '/' . $folderRel : '') . '/' . $newStoredName;
                    
                    $content = file_get_contents($srcPath);
                    if ($content !== false) {
                        $adapter->write($remotePath, $content, [
                            'original_name' => $file['original_name'],
                            'file_id' => 'pending',
                        ]);
                    }
                } catch (\Throwable $e) {
                    error_log("[OCTOPUS] Remote copy failed: " . $e->getMessage());
                }
            }
        }

        $newFileId = $db->insert('files', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'folder_id' => $targetFolderId,
            'original_name' => $file['original_name'],
            'stored_name' => $newStoredName,
            'mime_type' => $file['mime_type'],
            'size' => $file['size'],
            'checksum' => $file['checksum'],
            'file_key' => $file['file_key'],
            'version' => 1,
            'storage_location' => $file['storage_location'],
        ]);

        if (!$newFileId) {
            @unlink($dstPath);
            $this->json(['error' => 'Échec insertion en base de données'], 500);
            return;
        }

        try {
            $db->insert('audit_logs', [
                'tenant_id' => $user['tenant_id'],
                'user_id' => $user['id'],
                'action' => 'file.copied',
                'resource_type' => 'file',
                'resource_id' => $newFileId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'metadata' => json_encode([
                    'source_file_id' => $id,
                    'target_folder_id' => $targetFolderId,
                ]),
            ]);
        } catch (\Throwable $e) {}

        $this->json([
            'success' => true,
            'file' => [
                'id' => $newFileId,
                'name' => $file['original_name'],
                'size' => $file['size'],
                'mime_type' => $file['mime_type'],
                'folder_id' => $targetFolderId,
            ],
        ]);
    }
}
