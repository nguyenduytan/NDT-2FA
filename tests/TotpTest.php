<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ndtan\TwoFA\Totp\Totp;
final class TotpTest extends TestCase{
  private const SECRET='GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // '12345678901234567890'
  public function testVectorsSha1(): void{
    $vectors=[59=>'287082',1111111109=>'081804',1111111111=>'050471',1234567890=>'005924',2000000000=>'279037',20000000000=>'353130'];
    foreach($vectors as $ts=>$exp){ $code=Totp::totp(self::SECRET,period:30,digits:6,algo:'sha1',timestamp:$ts); $this->assertSame($exp,$code,"ts $ts"); }
  }
}
