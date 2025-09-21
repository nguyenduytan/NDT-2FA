# NDT 2FA (PHP) — v0.1.0

TOTP (RFC 6238) for PHP with **backup codes**, **otpauth URIs**, and **QR generation** (SVG via endroid/qr-code).  
Includes **Laravel** & **Symfony** integrations.

Namespace: `ndtan\TwoFA\*` · PHP 8.1+

## Features
- TOTP (SHA1/256/512), digits & period configurable
- Base32 secret generator
- Verification with time drift window
- otpauth URI builder
- Backup codes (generate + bcrypt hash/verify)
- QR generator (SVG Data URI)
- Laravel ServiceProvider, Symfony Command

## Install
```bash
composer require ndtan/php-2fa
```

## Plain PHP Usage
```php
use ndtan\TwoFA\Totp\Totp;
use ndtan\TwoFA\QR\EndroidQrProvider;

$secret = Totp::generateSecret();
$uri = Totp::buildOtpAuthUri('user@example.com', 'MyApp', $secret);
$qr  = (new EndroidQrProvider())->render($uri); // data:image/svg+xml;base64,...

$verify = Totp::verify($secret, $userInputCode, period:30, digits:6, algo:'sha1', window:1);
if ($verify['valid']) { /* success */ }
```

## Backup Codes
```php
use ndtan\TwoFA\Backup\BackupCodes;

$codes = BackupCodes::generate(10, 10);
// Store only hashes
$hashes = array_map(fn($c) => BackupCodes::hash($c), $codes);
// Verify later
$isValid = BackupCodes::verify($inputCode, $storedHash);
```

## Laravel
Auto-discovered provider binds:
- `ndt.twofa.totp`
- `ndt.twofa.qr`

```php
$secret = \ndtan\TwoFA\Totp\Totp::generateSecret();
$uri = \ndtan\TwoFA\Totp\Totp::buildOtpAuthUri($user->email, 'MyApp', $secret);
$qr = app('ndt.twofa.qr')->render($uri);
```

## Symfony
```bash
php bin/console ndt2fa:secret user@example.com MyApp
```

## Security Notes
- Store secrets encrypted at rest; backup codes as hashes
- Keep server time synced (NTP)
- Typical settings: period=30, digits=6, algo='sha1', window=1
