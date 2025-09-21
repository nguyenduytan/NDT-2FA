# Getting Started
```php
use ndtan\TwoFA\Totp\Totp;
$secret = Totp::generateSecret();
$code = Totp::totp($secret);
$result = Totp::verify($secret, $inputCode, window:1);
```