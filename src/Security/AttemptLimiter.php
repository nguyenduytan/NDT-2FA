<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security;
use ndtan\TwoFA\Security\Stores\RateStoreInterface;

final class AttemptLimiter
{
    public function __construct(
        private RateStoreInterface $store,
        private int $maxAttempts = 5,
        private int $perSeconds = 300,        // 5 minutes
        private int $lockoutSeconds = 300     // lock 5 minutes when exceeded
    ) {}

    private function counterKey(string $subject): string { return '2fa:cnt:'.$subject; }
    private function lockKey(string $subject): string { return '2fa:lock:'.$subject; }

    public function check(string $subject): array
    {
        $lockTtl = $this->store->ttl($this->lockKey($subject));
        if ($lockTtl > 0) {
            return ['allowed' => false, 'retry_after' => $lockTtl, 'remaining' => 0];
        }
        $count = (int)($this->store->get($this->counterKey($subject), 0));
        $remaining = max(0, $this->maxAttempts - $count);
        return ['allowed' => true, 'retry_after' => 0, 'remaining' => $remaining];
    }

    public function hit(string $subject): array
    {
        // increment count and maybe lock
        $count = $this->store->incr($this->counterKey($subject), 1, $this->perSeconds);
        if ($count > $this->maxAttempts) {
            $this->store->set($this->lockKey($subject), 1, $this->lockoutSeconds);
            $ttl = $this->store->ttl($this->lockKey($subject));
            return ['locked' => true, 'retry_after' => $ttl];
        }
        return ['locked' => false, 'retry_after' => 0, 'remaining' => max(0, $this->maxAttempts - $count)];
    }

    public function reset(string $subject): void
    {
        $this->store->set($this->counterKey($subject), 0, 0);
        $this->store->set($this->lockKey($subject), 0, 0);
    }
}
