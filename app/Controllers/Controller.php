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
        $user = $this->getAuthUser();
        $translation = \Saec\Core\Translation::getInstance();
        
        $layoutData = [
            'user' => $user,
            'isAdmin' => $user && $user['role'] === 'admin',
            'currentPage' => $this->getCurrentPage($view),
            'availableLangs' => \Saec\Core\Translation::getAvailable(),
            'currentLang' => $translation->getLang(),
        ];
        
        $data = array_merge($layoutData, $data);
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

    private function getCurrentPage(string $view): string
    {
        $map = [
            'dashboard/index' => 'dashboard',
            'files/index' => 'files',
            'shares/index' => 'shares',
            'settings/index' => 'settings',
            'admin/dashboard' => 'admin',
            'admin/tenants' => 'admin',
            'admin/modules' => 'admin',
            'admin/audit' => 'admin',
            'admin/config' => 'admin',
            'admin/users' => 'admin',
        ];
        return $map[$view] ?? basename($view);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->response->status($status)->json($data)->send();
    }

    /**
     * Extraire le token CSRF depuis header, body POST ou body JSON.
     */
    protected function extractCsrf(): string
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? '');
        if (empty($token)) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && str_contains($raw, '_token')) {
                $body = json_decode($raw, true);
                $token = is_array($body) ? ($body['_token'] ?? '') : '';
            }
        }
        return is_string($token) ? $token : '';
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
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                || (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data'))
                || (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded'));
            if ($isAjax) {
                $this->json(['error' => 'Non authentifié'], 401);
                return [];
            }
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
