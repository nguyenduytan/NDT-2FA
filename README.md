# NDT 2FA (PHP)

> TOTP (RFC 6238) for PHP with **backup codes**, **otpauth URIs**, and **QR generation** (SVG).  
> Ready for **plain PHP**, **Laravel**, and **Symfony**.

<p align="left">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white">
  <img alt="License" src="https://img.shields.io/badge/License-MIT-green">
  <img alt="Status" src="https://img.shields.io/badge/2FA-TOTP%20%7C%20Backup%20Codes%20%7C%20QR-blue">
</p>

---

## Table of Contents
- [Features](#features)
- [Installation](#installation)
- [Quick Start (Plain PHP)](#quick-start-plain-php)
- [Backup Codes](#backup-codes)
- [QR Codes](#qr-codes)
- [Framework Integrations](#framework-integrations)
  - [Laravel](#laravel)
  - [Symfony](#symfony)
- [Secure Verification (Rate Limit + Replay Protection)](#secure-verification-rate-limit--replay-protection)
- [API Reference](#api-reference)
- [Security Notes](#security-notes)
- [Testing](#testing)
- [License](#license)

---

## Features
- ✅ **TOTP (RFC 6238)** — SHA1 / SHA256 / SHA512; configurable `digits` / `period` / `window`
- ✅ **Base32 secrets** — generate & decode for authenticator apps
- ✅ **otpauth URIs** — compatible with Google Authenticator, Authy, etc.
- ✅ **Backup codes** — plaintext generation + secure `password_hash`/`password_verify`
- ✅ **QR generator (SVG Data URI)** — via `endroid/qr-code`
- ✅ **Security add-ons** — Attempt limiter, replay protection, unified `Verifier`
- ✅ **Frameworks** — Laravel ServiceProvider (auto-discovery) & Symfony console command
- ✅ **Zero-heavy deps** — Only `endroid/qr-code` for QR (core TOTP is dependency-free)

---

## Installation
```bash
composer require ndtan/php-2fa
```

> PHP **8.1+** is required.

---

## Quick Start (Plain PHP)
```php
<?php
use ndtan\TwoFA\Totp\Totp;
use ndtan\TwoFA\QR\EndroidQrProvider;

// 1) Generate a Base32 secret
$secret = Totp::generateSecret(); // e.g. "JBSWY3DPEHPK3PXP..."

// 2) Build otpauth URI
$uri = Totp::buildOtpAuthUri('user@example.com', 'MyApp', $secret);

// 3) Render QR (SVG Data URI)
$qr = (new EndroidQrProvider())->render($uri, 256, true); // "data:image/svg+xml;base64,..."

// 4) Verify user input (±1 step = ±30s window)
$result = Totp::verify($secret, $userInputCode, period: 30, digits: 6, algo: 'sha1', window: 1);
if ($result['valid']) {
    // success
}
```
> Tip: keep your server clock synchronized (NTP).

---

## Backup Codes
```php
use ndtan\TwoFA\Backup\BackupCodes;

// Generate one-time backup codes for the user
$codes  = BackupCodes::generate(count: 10, length: 10);

// Store **hashes** only
$hashes = array_map(fn($c) => BackupCodes::hash($c), $codes);

// Verify later
$isValid = BackupCodes::verify($inputBackupCode, $storedHash);
```

---

## QR Codes
The package includes an **SVG** QR provider (`EndroidQrProvider`) that returns a Data URI string you can embed directly in HTML `<img>` tags.

```php
$qrDataUri = (new EndroidQrProvider())->render($otpauthUri); // data:image/svg+xml;base64,...

// HTML
// <img src="<?= htmlspecialchars($qrDataUri, ENT_QUOTES) ?>" alt="Scan QR">
```

---

## Framework Integrations

### Laravel
- **Auto-discovered** provider: `ndtan\TwoFA\Laravel\NdtTwoFaServiceProvider`
- Container bindings:
  - `ndt.twofa.totp` → TOTP utilities
  - `ndt.twofa.qr` → QR provider (SVG)

**Example (Controller):**
```php
$secret = \ndtan\TwoFA\Totp\Totp::generateSecret();
$uri    = \ndtan\TwoFA\Totp\Totp::buildOtpAuthUri($user->email, 'MyApp', $secret);
$qr     = app('ndt.twofa.qr')->render($uri);
```

**Route middleware (optional):**
```php
// Alias 'ndt.2fa' is auto-registered by the ServiceProvider
Route::middleware('ndt.2fa')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});
```

### Symfony
- Console command:
```bash
php bin/console ndt2fa:secret user@example.com MyApp
```
Prints the **secret**, **otpauth URI**, and **QR (data URI)**.

---

## Secure Verification (Rate Limit + Replay Protection)
Use the built-in **AttemptLimiter**, **UsedCodeStore**, and **Verifier** to harden your flow.

```php
use ndtan\TwoFA\Security\Stores\ArrayRateStore;
use ndtan\TwoFA\Security\Stores\ArrayUsedCodeStore;
use ndtan\TwoFA\Security\AttemptLimiter;
use ndtan\TwoFA\Security\Verifier;

$limiter  = new AttemptLimiter(new ArrayRateStore(), maxAttempts: 5, perSeconds: 300, lockoutSeconds: 300);
$used     = new ArrayUsedCodeStore();
$verifier = new Verifier($limiter, $used); // defaults: period=30, digits=6, algo='sha1', window=1

$res = $verifier->verifyTotp('user:42', $secret, $userCode);
switch ($res['status']) {
  case 'ok':          /* mark session verified */ break;
  case 'rate_limited':/* advise retry in $res['retry_after'] seconds */ break;
  case 'replayed':    /* code already used in current period */ break;
  default:            /* invalid: $res['remaining'] attempts left */ break;
}
```

**Distributed cache (Redis/Memcached) via PSR‑16:**  
```php
$cache   = /* any PSR-16 CacheInterface implementation */;
$limiter = new AttemptLimiter(new \ndtan\TwoFA\Security\Stores\Psr16RateStore($cache));
```

> See `docs/RATE_LIMITING.md` and `docs/SECURITY.md` for details.

---

## API Reference

### `Totp`
- `generateSecret(int $bytes = 20): string`
- `hotp(string $secretBase32, int $counter, int $digits = 6, string $algo = 'sha1'): string`
- `totp(string $secretBase32, int $period = 30, int $digits = 6, string $algo = 'sha1', ?int $timestamp = null): string`
- `verify(string $secretBase32, string $code, int $period = 30, int $digits = 6, string $algo = 'sha1', int $window = 1, ?int $timestamp = null): array{valid:bool,delta:?int}`
- `buildOtpAuthUri(string $accountLabel, string $issuer, string $secretBase32, int $period = 30, int $digits = 6, string $algo = 'sha1'): string`

### `BackupCodes`
- `generate(int $count = 10, int $length = 10, ?string $alphabet = null): array`
- `hash(string $code): string`
- `verify(string $code, string $hash): bool`

### Security utilities
- `AttemptLimiter::__construct(RateStoreInterface $store, int $maxAttempts = 5, int $perSeconds = 300, int $lockoutSeconds = 300)`
- `Verifier::verifyTotp(string $subjectKey, string $secretBase32, string $code): array`
- `ArrayRateStore`, `Psr16RateStore`, `ArrayUsedCodeStore`

---

## Security Notes
- Store **TOTP secrets** encrypted at rest.
- Store **backup codes** as **hashes** only.
- Allow a small drift window (`window=1`) and keep servers time‑synced (NTP).
- Rate‑limit attempts and prevent code replay using the built‑in utilities.

---

## Testing
```bash
composer install
composer test
```

---

## License
MIT © Tony Nguyen
