<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security\Stores;

interface RateStoreInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttlSeconds): void;
    public function incr(string $key, int $delta = 1, int $ttlSeconds = 0): int;
    public function ttl(string $key): int; // -1 if not supported / not exists
}
