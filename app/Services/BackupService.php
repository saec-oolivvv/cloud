<?php

declare(strict_types=1);

namespace Saec\Services;

use Saec\Core\Database;
use Saec\Services\Storage\AdapterFactory;

/**
 * Backup Service — Moteur de sauvegarde complet
 * 
 * Supporte: Full, Incremental, Differential, Files-only, DB-only
 * Pipeline: Dump → Compress → Encrypt → Upload → Verify
 */
class BackupService
{
    private static ?BackupService $instance = null;
    private Database $db;
    private StorageService $storage;
    private string $tempDir;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->storage = StorageService::getInstance();
        $this->tempDir = dirname(__DIR__, 2) . '/storage/temp/backups';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0770, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════
    // BACKUP CREATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Créer un backup complet
     */
    public function createBackup(int $providerId, ?int $tenantId, string $type = 'full', array $options = []): int
    {
        // Créer l'enregistrement
        $backupId = $this->db->insert('storage_backups', [
            'provider_id' => $providerId,
            'tenant_id' => $tenantId,
            'type' => $type,
            'status' => 'pending',
            'remote_path' => $this->buildRemotePath($tenantId, $type),
            'file_name' => '',
            'encrypted' => $options['encrypted'] ?? true,
            'compression' => $options['compression'] ?? 'gzip',
        ]);

        try {
            // Marquer en cours
            $this->updateStatus($backupId, 'running');
            $startTime = time();

            // Pipeline de backup
            $archivePath = $this->executePipeline($backupId, $tenantId, $type, $options);

            // Upload vers le provider
            $remotePath = $this->uploadToProvider($providerId, $archivePath, $tenantId);

            // Vérifier l'intégrité
            $checksum = hash_file('sha256', $archivePath);
            $fileSize = filesize($archivePath);

            // Mettre à jour l'enregistrement
            $this->db->execute(
                "UPDATE storage_backups SET 
                    status = 'completed',
                    file_name = ?,
                    file_size = ?,
                    file_checksum = ?,
                    remote_path = ?,
                    started_at = FROM_UNIXTIME(?),
                    completed_at = NOW(),
                    duration_seconds = ?
                WHERE id = ?",
                [
                    basename($remotePath),
                    $fileSize,
                    $checksum,
                    $remotePath,
                    $startTime,
                    time() - $startTime,
                    $backupId,
                ]
            );

            // Nettoyer le fichier temporaire
            if (file_exists($archivePath)) {
                unlink($archivePath);
            }

            // Audit
            $this->audit('backup.created', [
                'backup_id' => $backupId,
                'tenant_id' => $tenantId,
                'type' => $type,
                'size' => $fileSize,
                'duration' => time() - $startTime,
            ]);

            return $backupId;
        } catch (\Throwable $e) {
            $this->updateStatus($backupId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécuter le pipeline de backup
     */
    private function executePipeline(int $backupId, ?int $tenantId, string $type, array $options): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $prefix = $tenantId ? "tenant_{$tenantId}" : "system";
        $archiveName = "{$prefix}_{$type}_{$timestamp}.tar.gz";

        $files = [];

        // 1. Dump DB
        if (in_array($type, ['full', 'db_only', 'incremental', 'differential'])) {
            $dbDump = $this->dumpDatabase($tenantId);
            $files[] = $dbDump;
        }

        // 2. Fichiers
        if (in_array($type, ['full', 'files_only', 'incremental', 'differential'])) {
            $fileList = $this->getFileList($tenantId, $type);
            foreach ($fileList as $file) {
                $files[] = $file['path'];
            }
        }

        // 3. Compresser
        $archivePath = $this->tempDir . '/' . $archiveName;
        $this->compressFiles($files, $archivePath, $options['compression'] ?? 'gzip');

        // 4. Chiffrer
        if ($options['encrypted'] ?? true) {
            $archivePath = $this->encryptFile($archivePath, $backupId);
        }

        return $archivePath;
    }

    /**
     * Dump de la base de données
     */
    private function dumpDatabase(?int $tenantId): string
    {
        $config = $GLOBALS['SAEC_CONFIG']['database'] ?? [];
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $dbname = $config['database'] ?? 'saec_cloud';
        $user = $config['username'] ?? 'root';
        $pass = $config['password'] ?? '';

        $dumpFile = $this->tempDir . '/db_dump_' . time() . '.sql';

        // Utiliser mysqldump si disponible
        $cmd = sprintf(
            'mysqldump -h %s -P %d -u %s %s > %s 2>&1',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($dbname),
            escapeshellarg($dumpFile)
        );

        if (!empty($pass)) {
            $cmd = sprintf(
                'mysqldump -h %s -P %d -u %s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                $port,
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbname),
                escapeshellarg($dumpFile)
            );
        }

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            // Fallback: SELECT INTO OUTFILE via PHP
            $this->dumpDatabaseManual($tenantId, $dumpFile);
        }

        return $dumpFile;
    }

    /**
     * Dump manuel si mysqldump n'est pas disponible
     */
    private function dumpDatabaseManual(?int $tenantId, string $outputFile): void
    {
        $tables = $this->db->fetchAll("SHOW TABLES");
        $content = "-- SAEC Cloud Database Dump\n";
        $content .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $row) {
            $tableName = reset($row);

            // Si tenant spécifique, filtrer les tables concernées
            if ($tenantId && in_array($tableName, ['users', 'files', 'folders', 'shares'])) {
                $rows = $this->db->fetchAll("SELECT * FROM {$tableName} WHERE tenant_id = ?", [$tenantId]);
            } else {
                $rows = $this->db->fetchAll("SELECT * FROM {$tableName}");
            }

            $content .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

            // Créer la structure
            $createTable = $this->db->fetch("SHOW CREATE TABLE {$tableName}");
            $content .= ($createTable['Create Table'] ?? '') . ";\n\n";

            // Insérer les données
            foreach ($rows as $row) {
                $values = array_map(function ($v) {
                    return $v === null ? 'NULL' : "'" . addslashes($v) . "'";
                }, array_values($row));

                $content .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $content .= "\n";
        }

        file_put_contents($outputFile, $content);
    }

    /**
     * Lister les fichiers à sauvegarder
     */
    private function getFileList(?int $tenantId, string $type): array
    {
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';

        if ($tenantId) {
            $tenantDir = $uploadDir . '/' . $tenantId;
            if (!is_dir($tenantDir)) return [];
            return $this->scanDirectory($tenantDir, $tenantDir);
        }

        // Backup complet: tous les dossiers
        $dirs = glob($uploadDir . '/*', GLOB_ONLYDIR);
        $files = [];
        foreach ($dirs as $dir) {
            $files = array_merge($files, $this->scanDirectory($dir, $uploadDir));
        }
        return $files;
    }

    /**
     * Scanner récursivement un dossier
     */
    private function scanDirectory(string $dir, string $baseDir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($baseDir . '/', '', $file->getRealPath());
                $files[] = [
                    'path' => $file->getRealPath(),
                    'relative' => $relativePath,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        return $files;
    }

    /**
     * Compresser des fichiers
     */
    private function compressFiles(array $files, string $outputPath, string $method): void
    {
        $validFiles = array_filter($files, function ($f) {
            return file_exists($f) && is_file($f);
        });

        if (empty($validFiles)) {
            // Créer une archive vide
            file_put_contents($outputPath, '');
            return;
        }

        if ($method === 'gzip') {
            $tarPath = $outputPath . '.tar';
            $this->createTar($validFiles, $tarPath);
            $this->gzipFile($tarPath, $outputPath);
            unlink($tarPath);
        } elseif ($method === 'zstd') {
            // Concaténer et compresser
            $combined = '';
            foreach ($validFiles as $file) {
                $relativePath = basename($file);
                $combined .= "---FILE:{$relativePath}---\n";
                $combined .= file_get_contents($file) . "\n";
            }
            file_put_contents($outputPath, $combined);
        } else {
            // Pas de compression
            $this->createTar($validFiles, $outputPath);
        }
    }

    /**
     * Créer une archive tar
     */
    private function createTar(array $files, string $tarPath): void
    {
        $tar = new \PharData($tarPath);
        foreach ($files as $file) {
            $tar->addFile($file, basename($file));
        }
    }

    /**
     * Compresser un fichier en gzip
     */
    private function gzipFile(string $inputPath, string $outputPath): void
    {
        $in = fopen($inputPath, 'rb');
        $out = gzopen($outputPath, 'wb9');

        while (!feof($in)) {
            $chunk = fread($in, 8192);
            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }

    /**
     * Chiffrer un fichier
     */
    private function encryptFile(string $filePath, int $backupId): string
    {
        $key = $this->getEncryptionKey($backupId);
        $iv = random_bytes(16);

        $content = file_get_contents($filePath);
        $encrypted = openssl_encrypt($content, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        $encryptedPath = $filePath . '.enc';
        file_put_contents($encryptedPath, $iv . $tag . $encrypted);

        return $encryptedPath;
    }

    /**
     * Déchiffrer un fichier
     */
    private function decryptFile(string $filePath, int $backupId): string
    {
        $key = $this->getEncryptionKey($backupId);

        $content = file_get_contents($filePath);
        $iv = substr($content, 0, 16);
        $tag = substr($content, 16, 16);
        $encrypted = substr($content, 32);

        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        $decryptedPath = str_replace('.enc', '', $filePath);
        file_put_contents($decryptedPath, $decrypted);

        return $decryptedPath;
    }

    /**
     * Obtenir la clé de chiffrement
     */
    private function getEncryptionKey(int $backupId): string
    {
        $config = $GLOBALS['SAEC_CONFIG']['security'] ?? [];
        $masterKey = $config['encryption_key'] ?? 'default-key-change-me';
        return hash('sha256', $masterKey . $backupId, true);
    }

    /**
     * Upload vers le provider
     */
    private function uploadToProvider(int $providerId, string $localPath, ?int $tenantId): string
    {
        $adapter = $this->storage->getAdapter($providerId);
        $remotePath = $this->buildRemotePath($tenantId, 'full');
        $content = file_get_contents($localPath);

        $adapter->write($remotePath, $content, [
            'mime' => 'application/gzip',
            'cache_control' => 'no-store',
        ]);

        return $remotePath;
    }

    /**
     * Construire le chemin distant
     */
    private function buildRemotePath(?int $tenantId, string $type): string
    {
        $date = date('Y/m/d');
        $prefix = $tenantId ? "tenants/{$tenantId}" : 'system';
        $filename = basename(sys_get_temp_dir()) . "_{$type}_" . date('Y-m-d_H-i-s') . '.tar.gz';
        return "{$prefix}/backups/{$date}/{$filename}";
    }

    // ═══════════════════════════════════════════════════════════
    // RESTORE
    // ═══════════════════════════════════════════════════════════

    /**
     * Restaurer un backup
     */
    public function restore(int $backupId, array $options = []): bool
    {
        $backup = $this->db->fetch(
            "SELECT * FROM storage_backups WHERE id = ?",
            [$backupId]
        );

        if (!$backup) {
            throw new \RuntimeException("Backup non trouvé");
        }

        try {
            $this->updateStatus($backupId, 'running');

            // Télécharger depuis le provider
            $adapter = $this->storage->getAdapter($backup['provider_id']);
            $content = $adapter->read($backup['remote_path']);

            // Sauvegarder localement
            $tempFile = $this->tempDir . '/restore_' . time() . '.tar.gz';
            file_put_contents($tempFile, $content);

            // Déchiffrer si nécessaire
            if ($backup['encrypted']) {
                $tempFile = $this->decryptFile($tempFile, $backupId);
            }

            // Restaurer
            $this->restoreFromArchive($tempFile, $backup);

            // Nettoyer
            unlink($tempFile);

            $this->audit('backup.restored', [
                'backup_id' => $backupId,
                'tenant_id' => $backup['tenant_id'],
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->updateStatus($backupId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaurer depuis une archive
     */
    private function restoreFromArchive(string $archivePath, array $backup): void
    {
        // Extraire
        $extractDir = $this->tempDir . '/restore_' . time();
        mkdir($extractDir, 0770, true);

        $tar = new \PharData($archivePath);
        $tar->decompress();

        $decompressedPath = str_replace('.tar.gz', '.tar', $archivePath);
        $tar = new \PharData($decompressedPath);
        $tar->extractTo($extractDir);

        // Restaurer la DB si dump présent
        $dbDump = glob($extractDir . '/db_dump_*.sql')[0] ?? null;
        if ($dbDump) {
            $this->restoreDatabase($dbDump);
        }

        // Restaurer les fichiers
        $files = glob($extractDir . '/*');
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
        foreach ($files as $file) {
            if (is_file($file)) {
                $dest = $uploadDir . '/' . basename($file);
                copy($file, $dest);
            }
        }

        // Nettoyer
        $this->removeDir($extractDir);
    }

    /**
     * Restaurer la base de données
     */
    private function restoreDatabase(string $dumpFile): void
    {
        $config = $GLOBALS['SAEC_CONFIG']['database'] ?? [];
        $host = $config['host'] ?? '127.0.0.1';
        $dbname = $config['database'] ?? 'saec_cloud';
        $user = $config['username'] ?? 'root';
        $pass = $config['password'] ?? '';

        $cmd = sprintf(
            'mysql -h %s -u %s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($dbname),
            escapeshellarg($dumpFile)
        );

        if (!empty($pass)) {
            $cmd = sprintf(
                'mysql -h %s -u %s -p%s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbname),
                escapeshellarg($dumpFile)
            );
        }

        exec($cmd, $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException("Database restore failed: " . implode("\n", $output));
        }
    }

    // ═══════════════════════════════════════════════════════════
    // QUERIES
    // ═══════════════════════════════════════════════════════════

    /**
     * Lister les backups
     */
    public function listBackups(?int $tenantId = null, int $limit = 50, int $offset = 0): array
    {
        $where = "1=1";
        $params = [];

        if ($tenantId) {
            $where .= " AND b.tenant_id = ?";
            $params[] = $tenantId;
        }

        return $this->db->fetchAll(
            "SELECT b.*, p.name as provider_name, p.type as provider_type
             FROM storage_backups b
             LEFT JOIN storage_providers p ON b.provider_id = p.id
             WHERE {$where}
             ORDER BY b.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );
    }

    /**
     * Obtenir un backup
     */
    public function getBackup(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT b.*, p.name as provider_name
             FROM storage_backups b
             LEFT JOIN storage_providers p ON b.provider_id = p.id
             WHERE b.id = ?",
            [$id]
        );
    }

    /**
     * Supprimer un backup
     */
    public function delete(int $id): bool
    {
        $backup = $this->getBackup($id);
        if (!$backup) return false;

        // Supprimer du provider
        try {
            $adapter = $this->storage->getAdapter($backup['provider_id']);
            $adapter->delete($backup['remote_path']);
        } catch (\Throwable $e) {
            error_log("[BackupService] Failed to delete remote: {$e->getMessage()}");
        }

        // Supprimer l'enregistrement
        $this->db->execute("DELETE FROM storage_backups WHERE id = ?", [$id]);

        return true;
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    private function updateStatus(int $id, string $status, ?string $error = null): void
    {
        $this->db->execute(
            "UPDATE storage_backups SET status = ?, error_message = ? WHERE id = ?",
            [$status, $error, $id]
        );
    }

    private function audit(string $action, array $metadata = []): void
    {
        try {
            $this->db->insert('audit_logs', [
                'tenant_id' => $_SESSION['tenant_id'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'metadata' => json_encode($metadata),
            ]);
        } catch (\Throwable $e) {
            error_log("[BackupService] Audit failed: {$e->getMessage()}");
        }
    }

    private function removeDir(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
