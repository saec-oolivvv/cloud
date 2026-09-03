<?php

declare(strict_types=1);

namespace Saec\Middleware;

use Saec\Core\Session;

class AuthMiddleware
{
    public function handle(array $params): bool
    {
        Session::start();

        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }

        return true;
    }
}
