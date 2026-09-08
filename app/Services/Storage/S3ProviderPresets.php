<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * S3 Provider Presets
 * 
 * Preset configurations for popular S3-compatible providers.
 */
class S3ProviderPresets
{
    public static function all(): array
    {
        return [
            'aws' => [
                'name' => 'Amazon S3',
                'endpoint' => 's3.amazonaws.com',
                'region' => 'us-east-1',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'cloudflare-r2' => [
                'name' => 'Cloudflare R2',
                'endpoint' => 'r2.cloudflarestorage.com',
                'region' => 'auto',
                'path_style' => true,
                'use_ssl' => true,
                'port' => 443,
            ],
            'minio' => [
                'name' => 'MinIO',
                'endpoint' => 'localhost',
                'region' => 'us-east-1',
                'path_style' => true,
                'use_ssl' => false,
                'port' => 9000,
            ],
            'digitalocean' => [
                'name' => 'DigitalOcean Spaces',
                'endpoint' => 'digitaloceanspaces.com',
                'region' => 'nyc3',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'wasabi' => [
                'name' => 'Wasabi',
                'endpoint' => 's3.wasabisys.com',
                'region' => 'us-east-1',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'backblaze' => [
                'name' => 'Backblaze B2',
                'endpoint' => 's3.us-west-004.backblazeb2.com',
                'region' => 'us-west-004',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'scaleway' => [
                'name' => 'Scaleway Object Storage',
                'endpoint' => 's3.fr-par.scw.cloud',
                'region' => 'fr-par',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'ovh' => [
                'name' => 'OVH Object Storage',
                'endpoint' => 's3.gra.cloud.ovh.net',
                'region' => 'gra',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'linode' => [
                'name' => 'Linode Object Storage',
                'endpoint' => 'us-east-1.linodeobjects.com',
                'region' => 'us-east-1',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
            'hetzner' => [
                'name' => 'Hetzner Object Storage',
                'endpoint' => 'fsn1.your-object-storage.com',
                'region' => 'fsn1',
                'path_style' => false,
                'use_ssl' => true,
                'port' => 443,
            ],
        ];
    }

    public static function get(string $provider): ?array
    {
        $presets = self::all();
        return $presets[$provider] ?? null;
    }

    public static function list(): array
    {
        $presets = self::all();
        return array_map(fn($p) => $p['name'], $presets);
    }
}
