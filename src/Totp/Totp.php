<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Totp;
use ndtan\TwoFA\Support\Base32;
final class Totp{
    public const DEFAULT_DIGITS=6, DEFAULT_PERIOD=30, DEFAULT_ALGO='sha1';
    public static function generateSecret(int $bytes=20):string{ return Base32::encode(random_bytes($bytes)); }
    public static function hotp(string $secretBase32,int $counter,int $digits=self::DEFAULT_DIGITS,string $algo=self::DEFAULT_ALGO):string{
        $key=Base32::decode($secretBase32); $binCounter=pack('N*',0).pack('N*',$counter);
        $hash=hash_hmac($algo,$binCounter,$key,true); $offset=ord($hash[19])&0xf;
        $code=((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|((ord($hash[$offset+3])&0xff));
        $otp=$code%(10**$digits); return str_pad((string)$otp,$digits,'0',STR_PAD_LEFT);
    }
    public static function totp(string $secretBase32,int $period=self::DEFAULT_PERIOD,int $digits=self::DEFAULT_DIGITS,string $algo=self::DEFAULT_ALGO,?int $timestamp=null):string{
        $timestamp=$timestamp??time(); $counter=intdiv($timestamp,$period); return self::hotp($secretBase32,$counter,$digits,$algo);
    }
    public static function verify(string $secretBase32,string $code,int $period=self::DEFAULT_PERIOD,int $digits=self::DEFAULT_DIGITS,string $algo=self::DEFAULT_ALGO,int $window=1,?int $timestamp=null):array{
        $timestamp=$timestamp??time(); $counter=intdiv($timestamp,$period); $code=trim($code);
        for($i=-$window;$i<=$window;$i++){ $calc=self::hotp($secretBase32,$counter+$i,$digits,$algo); if(hash_equals($calc,$code)){ return ['valid'=>true,'delta'=>$i]; } }
        return ['valid'=>false,'delta'=>null];
    }
    public static function buildOtpAuthUri(string $accountLabel,string $issuer,string $secretBase32,int $period=self::DEFAULT_PERIOD,int $digits=self::DEFAULT_DIGITS,string $algo=self::DEFAULT_ALGO):string{
        $label=rawurlencode($issuer.':'.$accountLabel); $params=http_build_query(['secret'=>$secretBase32,'issuer'=>$issuer,'period'=>$period,'digits'=>$digits,'algorithm'=>strtoupper($algo)]);
        return 'otpauth://totp/'.$label.'?'.$params;
    }
}
