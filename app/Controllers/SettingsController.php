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

        if (strlen($newPassword) < 8) {
            $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
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
}
