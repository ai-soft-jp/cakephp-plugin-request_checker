<?php
declare(strict_types=1);

namespace RequestChecker\Test\TestCase;

use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosDate;
use Cake\I18n\Date;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RequestChecker\Exception\RequestCheckException;
use RequestChecker\RequestChecker;

class RequestCheckerTest extends TestCase
{
    protected RequestChecker $checker;

    public function setUp(): void
    {
        $this->checker = new RequestChecker();
    }

    public function testSetDateClass()
    {
        $this->checker->setDateClass('chronos');
        $this->assertEquals('Cake\Chronos\ChronosDate', $this->checker->getDateClass());
        $this->checker->setDateClass('cakephp');
        $this->assertEquals('Cake\I18n\Date', $this->checker->getDateClass());
    }

    public function testSetDateClassNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->checker->setDateClass('Foo\Bar\Baz');
    }

    private static function _createUploadedFile()
    {
        $uploadedFileFactory = new UploadedFileFactory();
        $streamFactory = new StreamFactory();
        return $uploadedFileFactory->createUploadedFile($streamFactory->createStream(''));
    }

    public static function intValidProvider()
    {
        return [
            ['0'],
            ['1'],
            ['-2147483648'],
            ['2147483647'],
            [0],
            [2147483647],
        ];
    }

    #[DataProvider('intValidProvider')]
    public function testCheckIntValueValid($value)
    {
        $this->assertSame((int)$value, $this->checker->checkIntValue($value));
    }

    public static function intInvalidProvider()
    {
        return [
            [''],
            [' '],
            ['00'],
            ['0.1'],
            ['1foo'],
            ['DeadBeef'],
            ['-2147483649'],
            ['2147483648'],
            [0.1],
            [2147483648],
            [null],
        ];
    }

    #[DataProvider('intInvalidProvider')]
    public function testCheckIntValueInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkIntValue($value);
    }

    public function testCheckIntValueWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkIntValue('00', ['code' => 400]);
    }

    public static function intValidRangeProvider()
    {
        return [
            ['0', null, null],
            ['9223372036854775807', null, null],
            ['-9223372036854775808', null, null],
            ['0', 0, null],
            ['1', null, 1],
            [1, 0, 2],
        ];
    }

    #[DataProvider('intValidRangeProvider')]
    public function testCheckIntValueValidRange($value, $min, $max)
    {
        $this->assertSame((int)$value, $this->checker->checkIntValue($value, ['min' => $min, 'max' => $max]));
    }

    public static function intInvalidRangeProvider()
    {
        return [
            ['9223372036854775808', null, null],
            ['-9223372036854775809', null, null],
            ['0', 1, null],
            ['2', null, 1],
            ['0', 1, 2],
            ['3', 1, 2],
            [10, 20, 30],
        ];
    }

    #[DataProvider('intInvalidRangeProvider')]
    public function testCheckIntValueInvalidRange($value, $min, $max)
    {
        $this->expectException(RequestCheckException::class);
        $this->checker->checkIntValue($value, ['min' => $min, 'max' => $max]);
    }

    public function testCheckStringValid()
    {
        $this->assertSame('DeadBeef', $this->checker->checkString('DeadBeef'));
        $this->assertSame('DeadBeef', $this->checker->checkString(null, ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->checker->checkString('', ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->checker->checkString(' ', ['default' => 'DeadBeef']));
        $this->assertNull($this->checker->checkString(null, ['default' => null]));
        $this->assertNull($this->checker->checkString('', ['default' => null]));
        $this->assertNull($this->checker->checkString(' ', ['default' => null]));
    }

    public static function stringInvalidProvider()
    {
        return [
            [null],
            [''],
            [' '],
            [['foo' => 'bar']],
        ];
    }

    #[DataProvider('stringInvalidProvider')]
    public function testCheckStringInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkString($value);
    }

    public function testCheckStringWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkString('', ['code' => 400]);
    }

    public function testCheckStringRegexValid()
    {
        $this->assertSame('DeadBeef', $this->checker->checkStringRegex('DeadBeef', '/^\w+$/sD'));
        $this->assertSame('dead@beef', $this->checker->checkStringRegex('dead@beef', '/@/'));
        $this->assertSame('DeadBeef', $this->checker->checkStringRegex(null, '/@/', ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->checker->checkStringRegex('', '/@/', ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->checker->checkStringRegex(' ', '/@/', ['default' => 'DeadBeef']));
        $this->assertSame('', $this->checker->checkStringRegex(null, '/@/', ['default' => '']));
        $this->assertSame('', $this->checker->checkStringRegex(' ', '/@/', ['default' => '']));
        $this->assertNull($this->checker->checkStringRegex(null, '/@/', ['default' => null]));
        $this->assertNull($this->checker->checkStringRegex(' ', '/@/', ['default' => null]));
    }

    #[DataProvider('stringInvalidProvider')]
    public function testCheckStringRegexInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkStringRegex($value, '/@/');
    }

    public static function stringRegexUnmatchedProvider()
    {
        return [
            ['dead@beef', '/^\w+$/sD'],
            ['DeadBeef', '/@/'],
            [['foo' => 'dead@beef'], '/@/'],
        ];
    }

    #[DataProvider('stringRegexUnmatchedProvider')]
    public function testCheckStringRegexUnmatched($value, $regex)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkStringRegex($value, $regex);
    }

    public function testCheckStringRegexWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkStringRegex('', '/@/', ['code' => 400]);
    }

    public function testCheckIntValid()
    {
        $this->assertSame(0, $this->checker->checkInt('0'));
        $this->assertSame(1, $this->checker->checkInt('1'));
        $this->assertSame(-2147483648, $this->checker->checkInt('-2147483648'));
        $this->assertSame(2147483647, $this->checker->checkInt('2147483647'));
        $this->assertSame(0, $this->checker->checkInt(null, ['default' => 0]));
        $this->assertSame(1, $this->checker->checkInt('', ['default' => 1]));
        $this->assertSame(2, $this->checker->checkInt(' ', ['default' => 2]));
        $this->assertNull($this->checker->checkInt(null, ['default' => null]));
        $this->assertNull($this->checker->checkInt('', ['default' => null]));
        $this->assertNull($this->checker->checkInt(' ', ['default' => null]));
    }

    #[DataProvider('intInvalidProvider')]
    public function testCheckIntInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkInt($value);
    }

    public static function intInvalidTypeProvider()
    {
        return [
            [null],
            [['foo' => '1']],
            [self::_createUploadedFile()],
        ];
    }

    #[DataProvider('intInvalidTypeProvider')]
    public function testCheckIntInvalidType($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkInt($value);
    }

    public function testCheckIntInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkInt('', ['code' => 400]);
    }

    public function testCheckFloatValid()
    {
        $this->assertSame(0.0, $this->checker->checkFloat('0'));
        $this->assertSame(0.0, $this->checker->checkFloat(0));
        $this->assertSame(1.1, $this->checker->checkFloat('1.1'));
        $this->assertSame(1.1, $this->checker->checkFloat(1.1));
        $this->assertSame(1000000000.0, $this->checker->checkFloat('1e+9'));
        $this->assertSame(INF, $this->checker->checkFloat('1e+1000'));
        $this->assertSame(0.0, $this->checker->checkFloat(null, ['default' => 0.0]));
        $this->assertSame(1.0, $this->checker->checkFloat('', ['default' => 1.0]));
        $this->assertSame(2.0, $this->checker->checkFloat(' ', ['default' => 2.0]));
        $this->assertNull($this->checker->checkFloat(null, ['default' => null]));
        $this->assertNull($this->checker->checkFloat('', ['default' => null]));
        $this->assertNull($this->checker->checkFloat(' ', ['default' => null]));
    }

    public static function floatInvalidProvider()
    {
        return [
            [null],
            [''],
            [' '],
            ['deadbeef'],
            ['1e'],
            ['1e+1e'],
            [['foo' => '1.0']],
            [self::_createUploadedFile()],
        ];
    }

    #[DataProvider('floatInvalidProvider')]
    public function testCheckFloatInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkFloat($value);
    }

    public static function floatValidRangeProvider()
    {
        return [
            ['1000000000', null, 1e+9],
            ['-1000000000', -1e+9, null],
            ['0', -1, 1],
        ];
    }

    #[DataProvider('floatValidRangeProvider')]
    public function testCheckFloatValidRange($value, $min, $max)
    {
        $this->assertSame((float)$value, $this->checker->checkFloat($value, ['min' => $min, 'max' => $max]));
    }

    public static function floatInvalidRangeProvider()
    {
        return [
            ['1e+1000', null, PHP_FLOAT_MAX],
            ['-1000000000', -1e+8, null],
            ['1.0000000000001', -1, 1],
            [['foo' => '1.0'], 0, 1],
        ];
    }

    #[DataProvider('floatInvalidRangeProvider')]
    public function testCheckFloatInvalidRange($value, $min, $max)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkFloat($value, ['min' => $min, 'max' => $max]);
    }

    public function testCheckFloatInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkFloat(null, ['code' => 400]);
    }

    public function testCheckJSONValue()
    {
        $this->assertNull($this->checker->checkJSON('null', ['default' => 'DeadBeef']));
        $this->assertSame(false, $this->checker->checkJSON('false', ['default' => 'DeadBeef']));
        $this->assertSame(['dead' => 'beef'], $this->checker->checkJSON('{"dead":"beef"}'));
        $this->assertObjectHasProperty('dead', $this->checker->checkJSON('{"dead":"beef"}', ['assoc' => false]));
        $this->assertNull($this->checker->checkJSON(null, ['default' => null]));
        $this->assertNull($this->checker->checkJSON('', ['default' => null]));
        $this->assertNull($this->checker->checkJSON(' ', ['default' => null]));
    }

    public static function invalidJsonProvider()
    {
        return [
            [null],
            [''],
            ['NULL'],
            ['{dead:"beef"}'],
        ];
    }

    #[DataProvider('invalidJsonProvider')]
    public function testCheckJSONInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkJSON($value);
    }

    public function testCheckJSONInvalidDepth()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkJSON('[[[[]]]]', ['depth' => 2]);
    }

    public function testCheckJSONThrowOnError()
    {
        $this->expectException('JsonException');
        $this->checker->checkJSON('{', ['options' => JSON_THROW_ON_ERROR]);
    }

    public function testCheckDateValid()
    {
        $date = ChronosDate::create(2020, 1, 2);
        $this->assertEquals($date, $this->checker->checkDate('20200102'));
        $this->assertEquals($date, $this->checker->checkDate('2020-01-02'));
        $this->assertEquals($date, $this->checker->checkDate('2020/01/02'));
        $this->assertEquals($date, $this->checker->checkDate('Jan 2 2020'));
        $this->assertSame($date, $this->checker->checkDate(null, ['default' => $date]));
        $this->assertSame($date, $this->checker->checkDate('', ['default' => $date]));
        $this->assertSame($date, $this->checker->checkDate(' ', ['default' => $date]));
        $this->assertEquals($date, $this->checker->checkDate($date));
        $this->assertEquals($date, $this->checker->checkDate(new DateTime('2020-01-02 01:01:01')));
        $this->assertEquals($date, $this->checker->checkDate(new DateTimeImmutable('2020-01-02 01:01:01')));
        $this->assertEquals($date, $this->checker->checkDate(Chronos::parse('2020-01-02 01:01:01')));
        $this->assertNull($this->checker->checkDate(null, ['default' => null]));
        $this->assertNull($this->checker->checkDate('', ['default' => null]));
        $this->assertNull($this->checker->checkDate(' ', ['default' => null]));
    }

    public static function dateValidOptionsProvider()
    {
        return [
            ['20200102', ['formats' => 'Ymd']],
            ['2020-01-02', ['formats' => ['Ymd', 'Y-m-d']]],
            ['2020/01/02', ['formats' => ['Ymd', 'Y/m/d']]],
            ['01/02/2020', ['formats' => 'm/d/Y']],
            ['02/01/2020', ['formats' => 'd/m/Y']],
            ['Jan 2 2020', ['formats' => 'M j Y']],
            ['2020-01-02', ['min' => ChronosDate::create(2020, 1, 2)]],
            ['2020-01-02', ['min' => ChronosDate::create(2020, 1, 1)]],
            ['2020-01-02', ['max' => ChronosDate::create(2020, 1, 2)]],
            ['2020-01-02', ['max' => ChronosDate::create(2020, 1, 3)]],
            [ChronosDate::create(2020, 1, 2), ['formats' => 'Ymd']],
            [new DateTime('2020-01-02 01:01:01'), ['formats' => ['Y']]],
        ];
    }

    #[DataProvider('dateValidOptionsProvider')]
    public function testCheckDateValidFormats($value, $options)
    {
        $this->assertEquals(ChronosDate::create(2020, 1, 2), $this->checker->checkDate($value, $options));
    }

    public static function dateInvalidProvider()
    {
        return [
            [''],
            [' '],
            ['deadbeef'],
            ['2020-13-01'],
            ['2020-12-32'],
            [['foo' => '2020-01-01']],
            [self::_createUploadedFile()],
        ];
    }

    #[DataProvider('dateInvalidProvider')]
    public function testCheckDateInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkDate($value);
    }

    public static function dateInvalidOptionsProvider()
    {
        $date = ChronosDate::create(2020, 1, 1);
        return [
            ['2020/01/02', ['formats' => ['Y-m-d', 'Ymd']]],
            ['2020/01/32', ['formats' => 'Y/m/d']],
            ['2020/00/01', ['formats' => 'Y/m/d']],
            ['2019/Feb/01', ['formats' => 'Y/m/d']],
            [['foo' => '2020-01-01'], ['formats' => 'Y-m-d']],
            ['2000-01-01', ['min' => $date]],
            ['2030-01-01', ['max' => $date]],
        ];
    }

    #[DataProvider('dateInvalidOptionsProvider')]
    public function testCheckDateInvalidFormat($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkDate($value, $options);
    }

    public function testCheckDateInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkDate(null, ['code' => 400]);
    }

    public function testCheckDateEmptyStringFormats()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->checker->checkDate(null, ['formats' => '']);
    }

    public function testCheckDateEmptyArrayFormats()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->checker->checkDate(null, ['formats' => []]);
    }

    public function testCheckDateWithClass()
    {
        $this->checker->setDateClass('cakephp');
        $this->assertInstanceOf(Date::class, $this->checker->checkDate('today'));
    }

    public function testCheckArrayValid()
    {
        $this->assertSame(['foo'], $this->checker->checkArray(['foo']));
        $this->assertSame(['foo', 'bar'], $this->checker->checkArray(['foo', 'bar']));
        $this->assertSame(['foo'], $this->checker->checkArray(null, ['default' => ['foo']]));
        $this->assertSame(['bar'], $this->checker->checkArray('', ['default' => ['bar']]));
        $this->assertSame(['baz'], $this->checker->checkArray([], ['default' => ['baz']]));
        $this->assertSame([], $this->checker->checkArray([''], ['default' => []]));
        $this->assertNull($this->checker->checkArray(null, ['default' => null]));
        $this->assertNull($this->checker->checkArray('', ['default' => null]));
        $this->assertNull($this->checker->checkArray([], ['default' => null]));
        $this->assertNull($this->checker->checkArray([''], ['default' => null]));
    }

    public static function arrayInvalidProvider()
    {
        return [
            [null],
            [''],
            [' '],
            [[]],
            [['']],
            ['deadbeef'],
            [self::_createUploadedFile()],
            [[['foo', 'bar']]],
            [[self::_createUploadedFile()]],
        ];
    }

    #[DataProvider('arrayInvalidProvider')]
    public function testCheckArrayInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkArray($value);
    }

    public static function arrayValidOptionsProvider()
    {
        return [
            [['foo'], ['minElements' => 1]],
            [['foo', 'bar'], ['maxElements' => 2]],
            [[['foo', 'bar']], ['checkString' => false]],
            [['foo'], ['validator' => fn($v) => $v === 'foo']],
            [['foo', 'bar'], ['validator' => fn($v) => preg_match('/^\w+$/sD', $v)]],
        ];
    }

    #[DataProvider('arrayValidOptionsProvider')]
    public function testCheckArrayValidOptions($value, $options)
    {
        $this->assertSame($value, $this->checker->checkArray($value, $options));
    }

    public static function arrayInalidOptionsProvider()
    {
        return [
            [['foo'], ['minElements' => 2]],
            [['foo', 'bar'], ['maxElements' => 1]],
            [['foo', 'bar', 'baz'], ['minElements' => 1, 'maxElements' => 2]],
            [['fobar'], ['validator' => 'is_numeric']],
            [['foo', 'bar'], ['validator' => 'ctype_digit']],
        ];
    }

    #[DataProvider('arrayInalidOptionsProvider')]
    public function testCheckArrayInvalidOptions($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkArray($value, $options);
    }

    public function testCheckArrayInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkArray(null, ['code' => 400]);
    }

    public function testCheckStringArrayValid()
    {
        $this->assertSame(['foo'], $this->checker->checkStringArray(['foo']));
        $this->assertSame(['foo', 'bar'], $this->checker->checkStringArray(['foo', 'bar']));
        $this->assertSame(['foo'], $this->checker->checkStringArray(null, ['default' => ['foo']]));
        $this->assertSame(['bar'], $this->checker->checkStringArray('', ['default' => ['bar']]));
        $this->assertSame(['baz'], $this->checker->checkStringArray([], ['default' => ['baz']]));
        $this->assertSame([], $this->checker->checkStringArray([''], ['default' => []]));
        $this->assertNull($this->checker->checkStringArray(null, ['default' => null]));
        $this->assertNull($this->checker->checkStringArray('', ['default' => null]));
        $this->assertNull($this->checker->checkStringArray([], ['default' => null]));
        $this->assertNull($this->checker->checkStringArray([''], ['default' => null]));
    }

    public static function stringArrayValidOptionsProvider()
    {
        return [
            [['', ''], ['allowEmpty' => true]],
            [['foo', ''], ['allowEmpty' => true]],
            [['foo'], ['regex' => '/^\w+$/']],
            [['foo', ''], ['allowEmpty' => true, 'regex' => '/^\w+$/']],
            [['foo', 'bar'], ['regex' => '/^\w+$/']],
            [['100', '200'], ['validator' => 'is_numeric']],
            [['100', '200', ''], ['allowEmpty' => true, 'validator' => 'is_numeric']],
        ];
    }

    #[DataProvider('stringArrayValidOptionsProvider')]
    public function testCheckStringArrayValidOptions($value, $options)
    {
        $this->assertSame($value, $this->checker->checkStringArray($value, $options));
    }

    public function testCheckStringArrayConverter()
    {
        $this->assertSame(['FOO', 'BAR'], $this->checker->checkStringArray(['foo', 'bar'], ['converter' => 'strtoupper']));
    }

    #[DataProvider('arrayInvalidProvider')]
    public function testCheckStringArrayInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkStringArray($value);
    }

    #[DataProvider('stringInvalidProvider')]
    public function testCheckStringArrayInvalidValue($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkStringArray([$value]);
    }

    public static function stringArrayInvalidOptionsProvider()
    {
        return [
            [['foo', ''], ['allowEmpty' => true, 'regex' => '/^\d+$/']],
            [['foo', 'bar'], ['regex' => '/^\d+$/']],
            [['foo', 'bar'], ['validator' => 'is_numeric']],
        ];
    }

    #[DataProvider('stringArrayInvalidOptionsProvider')]
    public function testCheckStringArrayInvalidOptions($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkStringArray($value, $options);
    }

    public function testCheckStringArrayWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkStringArray(null, ['code' => 400]);
    }

    public function testCheckIntArrayValid()
    {
        $this->assertSame([1], $this->checker->checkIntArray(['1']));
        $this->assertSame([1, 2], $this->checker->checkIntArray(['1', '2']));
        $this->assertSame([1, 2], $this->checker->checkIntArray([1, 2]));
        $this->assertSame([1], $this->checker->checkIntArray(null, ['default' => [1]]));
        $this->assertSame([2], $this->checker->checkIntArray('', ['default' => [2]]));
        $this->assertSame([1, 2], $this->checker->checkIntArray([], ['default' => [1, 2]]));
        $this->assertSame([], $this->checker->checkIntArray([''], ['default' => []]));
        $this->assertNull($this->checker->checkIntArray(null, ['default' => null]));
        $this->assertNull($this->checker->checkIntArray('', ['default' => null]));
        $this->assertNull($this->checker->checkIntArray([], ['default' => null]));
        $this->assertNull($this->checker->checkIntArray([''], ['default' => null]));
    }

    #[DataProvider('arrayInvalidProvider')]
    public function testCheckIntArrayInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkIntArray($value);
    }

    #[DataProvider('stringInvalidProvider')]
    public function testCheckIntArrayInvalidString($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkIntArray([$value]);
    }

    #[DataProvider('intInvalidProvider')]
    public function testCheckIntArrayInvalidInteger($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkIntArray([$value]);
    }

    #[DataProvider('intInvalidTypeProvider')]
    public function testCheckIntArrayInvalidType($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkIntArray([$value]);
    }

    public static function intArrayValidOptionsProvider()
    {
        return [
            [[1, 2], ['1', '2'], ['min' => 0]],
            [[1, 2], ['1', '2'], ['max' => 10]],
            [[2, 4], ['2', '4'], ['validator' => fn($v) => $v % 2 === 0]],
        ];
    }

    #[DataProvider('intArrayValidOptionsProvider')]
    public function testCheckIntArrayValidOptions($expected, $value, $options)
    {
        $this->assertSame($expected, $this->checker->checkIntArray($value, $options));
    }

    public static function intArrayInvalidOptionsProvider()
    {
        return [
            [['1', '2'], ['min' => 10]],
            [['1', '2'], ['max' => 0]],
            [['2', '4'], ['validator' => fn($v) => $v % 2 !== 0]],
        ];
    }

    #[DataProvider('intArrayInvalidOptionsProvider')]
    public function testCheckIntArrayInvalidOptions($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->checker->checkIntArray($value, $options);
    }

    public function testCheckIntArrayWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->checker->checkIntArray(null, ['code' => 400]);
    }
}
