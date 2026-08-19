<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CatalogStore
{
    public const string CACHE_KEY = 'ai-usage.pricing.catalog';

    public const string DISK_PATH = 'ai-usage/pricing-catalog.json';

    public function __construct(protected CacheRepository $cache) {}

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    public function models(): array
    {
        $cached = $this->cache->get(self::CACHE_KEY);

        if (is_array($cached)) {
            /** @var array<string, array<string, array<string, float>>> $cached */
            return $cached;
        }

        return $this->fromDisk();
    }

    /**
     * @param  array<string, array<string, array<string, float>>>  $models
     */
    public function put(array $models, int $ttlHours): void
    {
        $this->cache->put(self::CACHE_KEY, $models, now()->addHours(max(1, $ttlHours)));
        $this->writeDisk($models);
    }

    public function hasFreshCache(): bool
    {
        return $this->cache->has(self::CACHE_KEY);
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    protected function fromDisk(): array
    {
        try {
            $disk = $this->disk();

            if (! $disk->exists(self::DISK_PATH)) {
                return [];
            }

            /** @var mixed $decoded */
            $decoded = json_decode($disk->get(self::DISK_PATH) ?? '', true);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, array<string, array<string, float>>>  $models
     */
    protected function writeDisk(array $models): void
    {
        try {
            $this->disk()->put(self::DISK_PATH, json_encode($models, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Offline persistence is best-effort; cache still holds the catalog.
        }
    }

    protected function disk(): Filesystem
    {
        return Storage::disk('local');
    }
}
