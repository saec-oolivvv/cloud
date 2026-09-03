<?php

declare(strict_types=1);

namespace Saec\Core;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function path(): string
    {
        return parse_url($this->uri(), PHP_URL_PATH) ?: '/';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonBody === null) {
            $this->jsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $this->post[$key] ?? $this->jsonBody[$key] ?? $default;
    }

    public function json(): ?array
    {
        if ($this->jsonBody === null) {
            $this->jsonBody = json_decode(file_get_contents('php://input'), true);
        }
        return $this->jsonBody;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['SERVER_PORT'] ?? 0) == 443
            || ($this->header('X-Forwarded-Proto') === 'https');
    }
}
