<?php

declare(strict_types=1);

namespace Saec\Middleware;

use Saec\Core\Session;

class AdminMiddleware
{
    public function handle(array $params): bool
    {
        Session::start();

        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo '<!DOCTYPE html><html><head><title>403</title><link rel="stylesheet" href="/css/app.css"></head><body><div class="auth-container"><div class="auth-box"><div class="auth-card"><h2 style="color: var(--error); margin-bottom: 16px;">Accès refusé</h2><p style="color: var(--text-secondary);">Vous n\'avez pas les droits d\'administration nécessaires.</p><a href="/dashboard" class="btn btn-secondary" style="margin-top: 16px;">Retour au dashboard</a></div></div></div></body></html>';
            exit;
        }

        return true;
    }
}
