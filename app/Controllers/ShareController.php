<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Mailer;

class ShareController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $shares = $db->fetchAll(
            "SELECT s.*, f.original_name, f.size
             FROM shares s
             JOIN files f ON s.file_id = f.id
             WHERE s.tenant_id = ?
             ORDER BY s.created_at DESC",
            [$user['tenant_id']]
        );

        $data = [
            'user' => $user,
            'shares' => $shares,
            'pageTitle' => 'Partages',
        ];

        $this->view('shares/index', $data);
    }

    public function share(string $fileId): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $file = $db->fetch(
            "SELECT * FROM files WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL",
            [$fileId, $user['tenant_id']]
        );

        if (!$file) {
            $this->json(['error' => 'File not found'], 404);
            return;
        }

        $shareId = $db->insert('shares', [
            'tenant_id' => $user['tenant_id'],
            'file_id' => $fileId,
            'created_by' => $user['id'],
            'shared_with' => !empty($_POST['share_with_email']) ? $_POST['share_with_email'] : null,
            'link_token' => bin2hex(random_bytes(32)),
            'expires_at' => $_POST['expires_at'] ?? null,
            'access_count' => 0,
        ]);

        // Envoyer email si partage avec un email
        if (!empty($_POST['share_with_email'])) {
            $mailer = new Mailer();
            $shareLink = 'https://cloud.saec.me/share/' . bin2hex(random_bytes(32));
            
            $mailer->sendShareNotification(
                $_POST['share_with_email'],
                $user['email'],
                $file['original_name'],
                $shareLink
            );
        }

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'share.created',
                    'resource_type' => 'share',
                    'resource_id' => $shareId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $share = $db->fetch("SELECT * FROM shares WHERE id = ?", [$shareId]);

        $this->json([
            'success' => true,
            'share' => $share,
        ]);
    }

    public function revoke(string $id): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $share = $db->fetch(
            "SELECT * FROM shares WHERE id = ? AND tenant_id = ? AND created_by = ?",
            [$id, $user['tenant_id'], $user['id']]
        );

        if (!$share) {
            $this->json(['error' => 'Share not found'], 404);
            return;
        }

        $db->delete('shares', 'id = ?', [$id]);

        try {
                $db->insert('audit_logs', [
                    'tenant_id' => $user['tenant_id'],
                    'user_id' => $user['id'],
                    'action' => 'share.revoked',
                    'resource_type' => 'share',
                    'resource_id' => $id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {}

        $this->json(['success' => true]);
    }
}