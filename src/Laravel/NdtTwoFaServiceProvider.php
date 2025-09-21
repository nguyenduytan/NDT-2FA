<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Laravel;
use Illuminate\Support\ServiceProvider; use ndtan\TwoFA\Totp\Totp; use ndtan\TwoFA\QR\EndroidQrProvider;
final class NdtTwoFaServiceProvider extends ServiceProvider{
    public function register():void{ $this->app->singleton('ndt.twofa.totp',fn()=>new Totp()); $this->app->singleton('ndt.twofa.qr',fn()=>new EndroidQrProvider()); }
}
