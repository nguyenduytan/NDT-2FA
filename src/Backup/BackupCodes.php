<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Backup;
final class BackupCodes{
    public static function generate(int $count=10,int $length=10,?string $alphabet=null):array{
        $alphabet=$alphabet??'23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; $codes=[]; for($i=0;$i<$count;$i++){ $codes[]=self::randStr($length,$alphabet); } return $codes;
    }
    public static function hash(string $code):string{ return password_hash($code,PASSWORD_BCRYPT); }
    public static function verify(string $code,string $hash):bool{ return password_verify($code,$hash); }
    private static function randStr(int $length,string $alphabet):string{ $out=''; $max=strlen($alphabet)-1; for($i=0;$i<$length;$i++){ $out.=$alphabet[random_int(0,$max)]; } return $out; }
}
