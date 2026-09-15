<?php

declare(strict_types=1);

namespace Saec\Middleware;

use Saec\Core\JWTHandler;
use Saec\Core\Database;

/**
 * Middleware d'authentification pour le client sync desktop.
 *
 * Attend un token JWT access (15min) via `Authorization: Bearer <token>`.
 * Vérifie que l'utilisateur existe, est actif, et appartient au tenant du token.
 */
class SyncApiMiddleware
{
    public function handle(array $params): bool
    {
        header('Content-Type: application/json');

        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($auth) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (empty($auth) || !preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
            return $this->deny('Token manquant');
        }

        $payload = JWTHandler::decode($m[1]);
        if (!$payload || !isset($payload->sub) || !isset($payload->tenant_id)) {
            return $this->deny('Token invalide ou expiré');
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, tenant_id, email, role, active FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL",
            [(int) $payload->sub]
        );

        if (!$user || (int) $user['tenant_id'] !== (int) $payload->tenant_id) {
            return $this->deny('Utilisateur non trouvé');
        }

        // Aucune session PHP — on stocke le contexte utilisateur pour le controller
        $_SERVER['SAEC_API_USER'] = $user;

        return true;
    }

    private function deny(string $reason): bool
    {
        http_response_code(401);
        echo json_encode(['error' => 'Non autorisé', 'error_description' => $reason]);
        exit;
    }
}