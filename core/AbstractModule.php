<?php

declare(strict_types=1);

namespace Saec\Core;

use Saec\Core\Interfaces\ModuleInterface;
use Saec\Core\Interfaces\ContainerInterface;

abstract class AbstractModule implements ModuleInterface
{
    protected ContainerInterface $container;
    protected array $config = [];
    protected bool $booted = false;

    public function register(ContainerInterface $container): void
    {
        $this->container = $container;
        $this->config = $GLOBALS['SAEC_CONFIG'][strtolower($this->getName())] ?? [];
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getDb(): \Saec\Core\Interfaces\DatabaseInterface
    {
        return $this->container->make(\Saec\Core\Interfaces\DatabaseInterface::class);
    }

    protected function getAuth(): \Saec\Core\Interfaces\AuthInterface
    {
        return $this->container->make(\Saec\Core\Interfaces\AuthInterface::class);
    }

    protected function getSecurity(): \Saec\Core\Interfaces\SecurityInterface
    {
        return $this->container->make(\Saec\Core\Interfaces\SecurityInterface::class);
    }

    protected function getAudit(): \Saec\Core\Interfaces\AuditInterface
    {
        return $this->container->make(\Saec\Core\Interfaces\AuditInterface::class);
    }

    protected function getEncryption(): \Saec\Core\Interfaces\EncryptionInterface
    {
        return $this->container->make(\Saec\Core\Interfaces\EncryptionInterface::class);
    }

    protected function getConfig(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? $default;
    }

    protected function getUploadDir(int $tenantId): string
    {
        $dir = dirname(__DIR__, 1) . '/storage/uploads/' . $tenantId;
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        return $dir;
    }
}
