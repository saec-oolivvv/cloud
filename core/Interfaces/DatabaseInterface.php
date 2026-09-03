<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface DatabaseInterface
{
    public function fetch(string $sql, array $params = []): ?array;
    public function fetchAll(string $sql, array $params = []): array;
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, string $where, array $params = []): bool;
    public function delete(string $table, string $where, array $params = []): bool;
    public function execute(string $sql, array $params = []): bool;
    public function count(string $table, string $where = '1=1', array $params = []): int;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}
