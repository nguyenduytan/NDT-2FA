<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Security;
use ndtan\TwoFA\Totp\Totp;

final class Verifier
{
    public function __construct(
        private AttemptLimiter $limiter,
        private UsedCodeStoreInterface $usedStore,
        private int $period = 30,
        private int $digits = 6,
        private string $algo = 'sha1',
        private int $window = 1
    ) {}

    /**
     * @return array{status:string, delta?:int, remaining?:int, retry_after?:int}
     *  status: ok | invalid | rate_limited | replayed
     */
    public function verifyTotp(string $subjectKey, string $secretBase32, string $code): array
    {
        // rate-limiting check
        $gate = $this->limiter->check($subjectKey);
        if (!$gate['allowed']) {
            return ['status' => 'rate_limited', 'retry_after' => $gate['retry_after']];
        }

        // basic code format check (digits only)
        if (!preg_match('/^\d{'.$this->digits.'}$/', trim($code))) {
            $hit = $this->limiter->hit($subjectKey);
            return ['status' => 'invalid', 'remaining' => $hit['remaining'] ?? 0, 'retry_after' => $hit['retry_after'] ?? 0];
        }

        // verify totp with window
        $res = Totp::verify($secretBase32, $code, $this->period, $this->digits, $this->algo, $this->window);
        if (!$res['valid']) {
            $hit = $this->limiter->hit($subjectKey);
            return ['status' => 'invalid', 'remaining' => $hit['remaining'] ?? 0, 'retry_after' => $hit['retry_after'] ?? 0];
        }

        // replay protection per subject: prevent reusing same code within the valid period
        $ttl = $this->period; // conservative TTL
        if (!$this->usedStore->consume('2fa:used:'.$subjectKey, $code, $ttl)) {
            return ['status' => 'replayed'];
        }

        // success -> reset attempts
        $this->limiter->reset($subjectKey);
        return ['status' => 'ok', 'delta' => $res['delta']];
    }
}
