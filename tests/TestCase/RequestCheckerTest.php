<?php
declare(strict_types=1);

namespace RequestChecker\Test\TestCase;

use Cake\Chronos\Date;
use Cake\Chronos\MutableDate;
use Cake\I18n\FrozenDate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RequestChecker;

class RequestCheckerTest extends TestCase
{
    public function tearDown(): void
    {
        RequestChecker\setDateClass('chronos');
    }

    public function testSetDateClass()
    {
        RequestChecker\setDateClass('chronos');
        $this->assertEquals(Date::class, RequestChecker\getDateClass());
        RequestChecker\setDateClass('cakephp');
        $this->assertEquals(FrozenDate::class, RequestChecker\getDateClass());
        RequestChecker\setDateClass(MutableDate::class);
        $this->assertEquals(MutableDate::class, RequestChecker\getDateClass());
    }

    public function testSetDateClassNotFound()
    {
        $this->expectException(ReflectionException::class);
        RequestChecker\setDateClass('Foo\Bar\Baz');
    }

    public function testSetDateClassInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        RequestChecker\setDateClass('DateTime');
    }
}
