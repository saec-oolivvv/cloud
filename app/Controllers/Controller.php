<?php

declare(strict_types=1);

namespace Saec\Controllers;

use Saec\Core\Response;
use Saec\Core\Session;

abstract class Controller
{
    protected Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: {$view}";
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->response->status($status)->json($data)->send();
    }

    protected function redirect(string $url, int $status = 302): void
    {
        $this->response->redirect($url, $status)->send();
    }

    protected function withSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function withError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function getAuthUser(): ?array
    {
        return Session::get('user');
    }

    protected function requireAuth(): array
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->redirect('/login');
            exit;
        }
        return $user;
    }

    protected function requireAdmin(): array
    {
        $user = $this->requireAuth();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        return $user;
    }

    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
