<?php

declare(strict_types=1);

namespace Saec\Core;

use Saec\Core\Interfaces\ModuleInterface;
use Saec\Core\Interfaces\ContainerInterface;

class ModuleManager
{
    private ContainerInterface $container;
    private array $modules = [];
    private array $loaded = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(string $moduleClass): void
    {
        if (!class_exists($moduleClass)) {
            throw new \RuntimeException("Module class not found: {$moduleClass}");
        }

        $module = new $moduleClass();

        if (!$module instanceof ModuleInterface) {
            throw new \RuntimeException("Module must implement ModuleInterface: {$moduleClass}");
        }

        $name = $module->getName();

        if (isset($this->modules[$name])) {
            throw new \RuntimeException("Module already registered: {$name}");
        }

        // Vérifier dépendances
        foreach ($module->getDependencies() as $dep) {
            if (!isset($this->modules[$dep])) {
                throw new \RuntimeException("Module '{$name}' requires '{$dep}' which is not registered");
            }
        }

        $module->register($this->container);
        $this->modules[$name] = $module;
    }

    public function bootAll(): void
    {
        foreach ($this->modules as $name => $module) {
            if (!in_array($name, $this->loaded)) {
                $module->boot();
                $this->loaded[] = $name;
            }
        }
    }

    public function boot(string $name): void
    {
        if (!isset($this->modules[$name])) {
            throw new \RuntimeException("Module not found: {$name}");
        }

        if (!in_array($name, $this->loaded)) {
            $this->modules[$name]->boot();
            $this->loaded[] = $name;
        }
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    public function getLoaded(): array
    {
        return $this->loaded;
    }

    public function getAll(): array
    {
        return $this->modules;
    }

    public function getVersion(string $name): string
    {
        return $this->modules[$name]->getVersion() ?? '0.0.0';
    }

    public function getSystemVersion(): array
    {
        $versions = [];
        foreach ($this->modules as $name => $module) {
            $versions[$name] = $module->getVersion();
        }
        return $versions;
    }
}
