<?php

declare(strict_types=1);

namespace Saec\Core;

use Saec\Core\Interfaces\ContainerInterface;

class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];

    public function bind(string $key, mixed $instance): void
    {
        $this->bindings[$key] = $instance;
        unset($this->instances[$key], $this->singletons[$key]);
    }

    public function singleton(string $key, mixed $instance): void
    {
        $this->singletons[$key] = $instance;
        unset($this->bindings[$key], $this->instances[$key]);
    }

    public function make(string $key): mixed
    {
        // Singleton cache
        if (isset($this->singletons[$key]) && is_object($this->singletons[$key])) {
            return $this->singletons[$key];
        }

        // Instance cache
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // Binding
        if (isset($this->bindings[$key])) {
            $instance = $this->bindings[$key];
            if (is_string($instance) && class_exists($instance)) {
                $instance = $this->resolve($instance);
            }
            $this->instances[$key] = $instance;
            return $instance;
        }

        // Auto-resolve
        if (class_exists($key)) {
            return $this->resolve($key);
        }

        throw new \RuntimeException("Service '{$key}' not found in container");
    }

    public function has(string $key): bool
    {
        return isset($this->bindings[$key]) || isset($this->instances[$key]) || isset($this->singletons[$key]) || class_exists($key);
    }

    private function resolve(string $class): object
    {
        $ref = new \ReflectionClass($class);

        if ($ref->isAbstract() || $ref->isInterface()) {
            throw new \RuntimeException("Cannot instantiate abstract class or interface: {$class}");
        }

        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $params = $constructor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin() && class_exists($type->getName())) {
                $args[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("Cannot resolve parameter: \${$param->getName()} of {$class}");
            }
        }

        return $ref->newInstanceArgs($args);
    }

    public function getBindings(): array
    {
        return array_keys($this->bindings + $this->singletons);
    }
}
