<?php
declare(strict_types=1);

namespace RequestChecker\Test\TestCase;

use Phake;
use PHPUnit\Framework\TestCase;
use RequestChecker\RequestChecker;
use RequestChecker\RequestCheckerTrait;

class RequestCheckerTraitTest extends TestCase
{
    use RequestCheckerTrait;

    public function testSetRequestChecker()
    {
        $this->_setRequestChecker();
        $this->assertInstanceOf('RequestChecker\RequestChecker', $this->_getRequestChecker());

        $checker = new RequestChecker();
        $this->_setRequestChecker($checker);
        $this->assertSame($checker, $this->_getRequestChecker());
    }

    public function testCall()
    {
        $checker = Phake::mock(RequestChecker::class);
        Phake::when($checker)->checkString(Phake::anyParameters())->thenReturn('oki doki');
        $this->_setRequestChecker($checker);
        $this->assertEquals('oki doki', $this->_checkString('dead beef'));
    }
}
