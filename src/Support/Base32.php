<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Support;
final class Base32{
    private const ALPHABET='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; private const PAD='=';
    public static function encode(string $bin):string{ $a=self::ALPHABET;$bits=''; foreach(str_split($bin) as $c){$bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);} $out=''; for($i=0;$i<strlen($bits);$i+=5){$chunk=substr($bits,$i,5); if(strlen($chunk)<5){$chunk=str_pad($chunk,5,'0',STR_PAD_RIGHT);} $out.=$a[bindec($chunk)]; } while((strlen($out)%8)!==0)$out.=self::PAD; return $out; }
    public static function decode(string $s):string{ $s=strtoupper(preg_replace('/\s+/','',$s)); $s=rtrim($s,self::PAD); $a=self::ALPHABET;$bits=''; $len=strlen($s); for($i=0;$i<$len;$i++){ $pos=strpos($a,$s[$i]); if($pos===false){ throw new \InvalidArgumentException('Invalid Base32 character: '.$s[$i]); } $bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT); } $out=''; for($i=0;$i+8<=strlen($bits);$i+=8){ $out.=chr(bindec(substr($bits,$i,8))); } return $out; }
}
