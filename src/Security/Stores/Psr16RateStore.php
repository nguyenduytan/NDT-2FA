<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security\Stores;
use Psr\SimpleCache\CacheInterface;

final class Psr16RateStore implements RateStoreInterface
{
    public function __construct(private CacheInterface $cache) {}

    public function get(string $key, mixed $default = null): mixed
    { return $this->cache->get($key, $default); }

    public function set(string $key, mixed $value, int $ttlSeconds): void
    { $this->cache->set($key, $value, $ttlSeconds); }

    public function incr(string $key, int $delta = 1, int $ttlSeconds = 0): int
    {
        $current = (int)$this->cache->get($key, 0);
        $current += $delta;
        $this->cache->set($key, $current, $ttlSeconds > 0 ? $ttlSeconds : null);
        return $current;
    }

    public function ttl(string $key): int
    { return -1; }
}
