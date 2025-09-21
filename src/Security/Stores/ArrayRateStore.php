<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security\Stores;

final class ArrayRateStore implements RateStoreInterface
{
    private array $data = [];
    private array $exp = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->gc();
        return $this->data[$key] ?? $default;
    }
    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->data[$key] = $value;
        $this->exp[$key] = $ttlSeconds > 0 ? (time() + $ttlSeconds) : 0;
    }
    public function incr(string $key, int $delta = 1, int $ttlSeconds = 0): int
    {
        $this->gc();
        $v = (int)($this->data[$key] ?? 0) + $delta;
        $this->data[$key] = $v;
        if (!isset($this->exp[$key]) || $this->exp[$key] === 0) {
            $this->exp[$key] = $ttlSeconds > 0 ? (time() + $ttlSeconds) : 0;
        }
        return $v;
    }
    public function ttl(string $key): int
    {
        $this->gc();
        if (!isset($this->exp[$key])) return -1;
        $e = $this->exp[$key];
        if ($e === 0) return -1;
        $t = $e - time();
        return $t > 0 ? $t : -1;
    }
    private function gc(): void
    {
        $now = time();
        foreach ($this->exp as $k => $e) {
            if ($e !== 0 && $e <= $now) {
                unset($this->exp[$k], $this->data[$k]);
            }
        }
    }
}
