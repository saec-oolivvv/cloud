<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Database;
use Saec\Core\Encryption;
use Saec\Core\Session;

class SettingsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $db = Database::getInstance();

        $fullUser = $db->fetch(
            "SELECT u.*, t.name as tenant_name, t.storage_quota
             FROM users u
             LEFT JOIN tenants t ON u.tenant_id = t.id
             WHERE u.id = ?",
            [$user['id']]
        );

        $usage = $db->fetch(
            "SELECT COALESCE(SUM(size), 0) as used FROM files WHERE tenant_id = ? AND deleted_at IS NULL",
            [$user['tenant_id']]
        );

        $sessionCount = $db->fetch(
            "SELECT COUNT(*) as cnt FROM user_sessions WHERE user_id = ?",
            [$user['id']]
        );

        $recentSessions = $db->fetchAll(
            "SELECT ip_address, user_agent, last_activity, created_at
             FROM user_sessions WHERE user_id = ?
             ORDER BY last_activity DESC LIMIT 5",
            [$user['id']]
        );

        $this->view('settings/index', [
            'profile' => $fullUser,
            'used' => (int) ($usage['used'] ?? 0),
            'sessionCount' => (int) ($sessionCount['cnt'] ?? 0),
            'recentSessions' => $recentSessions,
            'pageTitle' => 'Paramètres',
        ]);
    }

    public function updateProfile(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $token = $_POST['_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->json(['error' => 'Nom requis'], 400);
            return;
        }

        $db = Database::getInstance();
        $db->execute(
            "UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?",
            [$name, $user['id']]
        );

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'profile.updated',
            'resource_type' => 'user',
            'resource_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $this->json(['success' => true, 'message' => 'Profil mis à jour']);
    }

    public function changePassword(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $token = $_POST['_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            $this->json(['error' => 'Tous les champs sont requis'], 400);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->json(['error' => 'Les mots de passe ne correspondent pas'], 400);
            return;
        }

        // Rate limit: max 5 password changes per hour
        $db = Database::getInstance();
        $recentChanges = $db->fetch(
            "SELECT COUNT(*) as cnt FROM audit_logs WHERE user_id = ? AND action = 'password.changed' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$user['id']]
        );
        if (($recentChanges['cnt'] ?? 0) >= 5) {
            $this->json(['error' => 'Trop de changements. Réessayez dans 1 heure.'], 429);
            return;
        }

        // Password complexity: min 12 chars, uppercase, lowercase, digit, special
        if (strlen($newPassword) < 12) {
            $this->json(['error' => 'Le mot de passe doit contenir au moins 12 caractères'], 400);
            return;
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $this->json(['error' => 'Le mot de passe doit contenir au moins une majuscule'], 400);
            return;
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $this->json(['error' => 'Le mot de passe doit contenir au moins une minuscule'], 400);
            return;
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $this->json(['error' => 'Le mot de passe doit contenir au moins un chiffre'], 400);
            return;
        }
        if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $this->json(['error' => 'Le mot de passe doit contenir au moins un caractère spécial'], 400);
            return;
        }

        $db = Database::getInstance();
        $fullUser = $db->fetch(
            "SELECT password_hash FROM users WHERE id = ?",
            [$user['id']]
        );

        if (!Encryption::verifyPassword($currentPassword, $fullUser['password_hash'])) {
            $this->json(['error' => 'Mot de passe actuel incorrect'], 400);
            return;
        }

        $newHash = Encryption::hashPassword($newPassword);
        $db->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$newHash, $user['id']]
        );

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'password.changed',
            'resource_type' => 'user',
            'resource_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $this->json(['success' => true, 'message' => 'Mot de passe changé avec succès']);
    }

    public function destroySessions(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $token = $_POST['_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $db = Database::getInstance();
        $db->execute(
            "DELETE FROM user_sessions WHERE user_id = ?",
            [$user['id']]
        );

        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'sessions.destroyed_all',
            'resource_type' => 'user',
            'resource_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $this->json(['success' => true, 'message' => 'Toutes les sessions ont été détruites']);
    }

    /**
     * RGPD Art. 20 — Export des données personnelles (JSON)
     */
    public function exportData(): void
    {
        $user = $this->requireAuth();

        $db = Database::getInstance();
        $userId = $user['id'];
        $tenantId = $user['tenant_id'];

        // Profil
        $profile = $db->fetch(
            "SELECT email, name, role, created_at, last_login FROM users WHERE id = ?",
            [$userId]
        );

        // Fichiers
        $files = $db->fetchAll(
            "SELECT original_name, mime_type, size, created_at FROM files WHERE tenant_id = ? AND deleted_at IS NULL",
            [$tenantId]
        );

        // Partages
        $shares = $db->fetchAll(
            "SELECT s.created_at, s.expires_at, f.original_name
             FROM shares s
             JOIN files f ON s.file_id = f.id
             WHERE s.tenant_id = ? AND s.user_id = ?",
            [$tenantId, $userId]
        );

        // Audit logs
        $logs = $db->fetchAll(
            "SELECT action, resource_type, created_at, ip_address
             FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 500",
            [$userId]
        );

        $export = [
            'export_date' => date('c'),
            'profile' => $profile,
            'files' => $files,
            'shares' => $shares,
            'audit_logs' => $logs,
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="saec-cloud-export-' . date('Y-m-d') . '.json"');
        header('Cache-Control: no-store');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * RGPD Art. 17 — Demande de suppression de compte
     */
    public function deleteAccount(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
            return;
        }

        $token = $_POST['_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            $this->json(['error' => 'Token CSRF invalide'], 403);
            return;
        }

        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            $this->json(['error' => 'Mot de passe requis pour confirmer'], 400);
            return;
        }

        $db = Database::getInstance();
        $fullUser = $db->fetch(
            "SELECT password_hash FROM users WHERE id = ?",
            [$user['id']]
        );

        if (!Encryption::verifyPassword($password, $fullUser['password_hash'])) {
            $this->json(['error' => 'Mot de passe incorrect'], 400);
            return;
        }

        // Soft-delete user
        $db->execute(
            "UPDATE users SET active = 0, email = CONCAT('deleted_', id, '_', email), updated_at = NOW() WHERE id = ?",
            [$user['id']]
        );

        // Delete sessions
        $db->execute("DELETE FROM user_sessions WHERE user_id = ?", [$user['id']]);

        // Audit log
        $db->insert('audit_logs', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'action' => 'account.deleted',
            'resource_type' => 'user',
            'resource_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        Session::destroy();
        $this->json(['success' => true, 'message' => 'Compte supprimé avec succès']);
    }
}
