<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface ContainerInterface
{
    public function bind(string $key, mixed $instance): void;
    public function singleton(string $key, mixed $instance): void;
    public function make(string $key): mixed;
    public function has(string $key): bool;
}
