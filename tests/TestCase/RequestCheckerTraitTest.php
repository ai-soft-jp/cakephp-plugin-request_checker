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
        $this->setRequestChecker();
        $this->assertInstanceOf('RequestChecker\RequestChecker', $this->getRequestChecker());

        $checker = new RequestChecker();
        $this->setRequestChecker($checker);
        $this->assertSame($checker, $this->getRequestChecker());
    }

    public function testCall()
    {
        $checker = Phake::mock(RequestChecker::class);
        Phake::when($checker)->checkString(Phake::anyParameters())->thenReturn('oki doki');
        $this->setRequestChecker($checker);
        $this->assertEquals('oki doki', $this->_checkString('dead beef'));
    }
}
