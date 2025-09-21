<?php
declare(strict_types=1);
namespace ndtan\TwoFA\QR;
use Endroid\QrCode\QrCode; use Endroid\QrCode\Writer\SvgWriter;
final class EndroidQrProvider implements QrProviderInterface{
    public function render(string $content,int $size=256,bool $asDataUri=true):string{
        $qr=QrCode::create($content)->setSize($size)->setMargin(8); $writer=new SvgWriter(); $svg=$writer->write($qr)->getString();
        return $asDataUri ? 'data:image/svg+xml;base64,'.base64_encode($svg) : $svg;
    }
}
