<?php

declare(strict_types=1);

namespace Saec\Core\Interfaces;

interface AuditInterface
{
    public function log(int $tenantId, ?int $userId, string $action, ?string $resourceType = null, ?int $resourceId = null, ?array $metadata = null): void;
    public function getLogs(int $tenantId, int $limit = 50, int $offset = 0, ?string $action = null): array;
    public function getStats(int $tenantId, int $days = 30): array;
}
