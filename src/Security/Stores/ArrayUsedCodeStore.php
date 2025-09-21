<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security\Stores;
use ndtan\TwoFA\Security\UsedCodeStoreInterface;

final class ArrayUsedCodeStore implements UsedCodeStoreInterface
{
    private array $codes = []; // key => [ codeHash => expiry ]
    public function consume(string $key, string $code, int $ttlSeconds): bool
    {
        $this->gc();
        $hash = hash('sha256', $key.'|'.$code);
        $bucket = $this->codes[$key] ?? [];
        if (isset($bucket[$hash]) && $bucket[$hash] > time()) {
            return false; // already used
        }
        $bucket[$hash] = time() + $ttlSeconds;
        $this->codes[$key] = $bucket;
        return true;
    }
    public function wasUsed(string $key, string $code): bool
    {
        $this->gc();
        $hash = hash('sha256', $key.'|'.$code);
        return isset(($this->codes[$key] ?? [])[$hash]) && $this->codes[$key][$hash] > time();
    }
    private function gc(): void
    {
        $now = time();
        foreach ($this->codes as $k => $bucket) {
            foreach ($bucket as $h => $exp) {
                if ($exp <= $now) unset($bucket[$h]);
            }
            if ($bucket) $this->codes[$k] = $bucket; else unset($this->codes[$k]);
        }
    }
}
