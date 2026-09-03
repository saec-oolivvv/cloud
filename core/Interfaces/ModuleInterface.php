<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface ModuleInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function getDependencies(): array;
    public function register(ContainerInterface $container): void;
    public function boot(): void;
}
