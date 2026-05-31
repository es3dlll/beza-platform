<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

final class CacheOrchestrator
{
    private const NAMESPACE_SEPARATOR = ':';

    public function __construct(
        private readonly Cache $cache,
    ) {}

    public function cacheAside(string $namespace, string $key, int $ttl, Closure $fallback): mixed
    {
        $cacheKey = $this->key($namespace, $key);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $value = $fallback();
        $this->cache->put($cacheKey, $value, $ttl);

        return $value;
    }

    public function writeThrough(string $namespace, string $key, mixed $value, int $ttl): void
    {
        $this->cache->put($this->key($namespace, $key), $value, $ttl);
    }

    public function invalidate(string $namespace, ?string $key = null): void
    {
        if ($key !== null) {
            $this->cache->forget($this->key($namespace, $key));
            return;
        }

        $prefix = $this->key($namespace, '');
        $store = $this->cache->getStore();

        if (method_exists($store, 'connection') && method_exists($store->connection(), 'keys')) {
            foreach ($store->connection()->keys($prefix . '*') as $k) {
                $this->cache->forget($k);
            }
        }
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return $this->cacheAside('global', $key, $ttl, $callback);
    }

    public function get(string $namespace, string $key): mixed
    {
        return $this->cache->get($this->key($namespace, $key));
    }

    public function has(string $namespace, string $key): bool
    {
        return $this->cache->has($this->key($namespace, $key));
    }

    private function key(string $namespace, string $key): string
    {
        $app = Str::slug(config('app.name', 'beza'), '_');
        return "{$app}{self::NAMESPACE_SEPARATOR}{$namespace}{self::NAMESPACE_SEPARATOR}{$key}";
    }
}
