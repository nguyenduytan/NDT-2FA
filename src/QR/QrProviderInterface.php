<?php
declare(strict_types=1);
namespace ndtan\TwoFA\QR;
interface QrProviderInterface{ public function render(string $content,int $size=256,bool $asDataUri=true):string; }
