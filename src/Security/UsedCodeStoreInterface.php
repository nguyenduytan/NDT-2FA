<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security;

interface UsedCodeStoreInterface
{
    /** mark a code as used for TTL seconds (idempotent) */
    public function consume(string $key, string $code, int $ttlSeconds): bool;
    /** check if code was used */
    public function wasUsed(string $key, string $code): bool;
}
