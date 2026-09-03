<?php

declare(strict_types=1);

namespace Saec\Core;

class ModularRouter
{
    private array $routes = [];
    private array $middlewares = [];
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middlewares): void
    {
        $pattern = $this->buildPattern($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Run middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                        $mw = new $middlewareClass();
                        $result = $mw->handle();
                        if ($result === false) {
                            return;
                        }
                    }
                }

                // Extract params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Dispatch
                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $controller = $this->container->make($class);
                    $controller->$method(...array_values($params));
                } elseif (is_callable($handler)) {
                    $handler(...array_values($params));
                }

                return;
            }
        }

        // 404
        http_response_code(404);
        $errorView = dirname(__DIR__, 1) . '/Modules/Error/Views/404.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '404 — Not Found';
        }
    }

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function loadModuleRoutes(ModuleInterface $module): void
    {
        $name = strtolower($module->getName());
        $routesFile = dirname(__DIR__, 1) . "/Modules/{$name}/routes.php";

        if (file_exists($routesFile)) {
            $router = $this;
            require $routesFile;
        }
    }
}
