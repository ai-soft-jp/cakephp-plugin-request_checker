<?php
declare(strict_types=1);

namespace RequestChecker\Test\TestCase\Exception;

use PHPUnit\Framework\TestCase;
use RequestChecker\Exception\RequestCheckException;
use RuntimeException;

class RequestCheckExceptionTest extends TestCase
{
    public function testDefaultMessage()
    {
        $this->expectExceptionMessage('Invalid parameter');
        throw new RequestCheckException();
    }

    public function testMessage()
    {
        $this->expectExceptionMessage('Dead Beef');
        throw new RequestCheckException('Dead Beef');
    }

    public function testDefaultCode()
    {
        $this->expectExceptionCode(404);
        throw new RequestCheckException();
    }

    public function testCode()
    {
        $this->expectExceptionCode(400);
        throw new RequestCheckException(null, 400);
    }

    public function testPrevious()
    {
        $exception = new RequestCheckException(null, null, new RuntimeException());
        $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
    }
}
