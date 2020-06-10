<?php
declare(strict_types=1);

namespace RequestChecker\Test\TestCase;

use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Cake\I18n\FrozenDate;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use PHPUnit\Framework\TestCase;
use RequestChecker;
use RequestChecker\Exception\RequestCheckException;
use RequestChecker\RequestCheckerTrait;

class RequestCheckerTraitTest extends TestCase
{
    use RequestCheckerTrait;

    public function tearDown(): void
    {
        RequestChecker\setDateClass('chronos');
    }

    private function _createUploadedFile()
    {
        $uploadedFileFactory = new UploadedFileFactory();
        $streamFactory = new StreamFactory();
        return $uploadedFileFactory->createUploadedFile($streamFactory->createStream(''));
    }

    public function intValidProvider()
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

    /**
     * @dataProvider intValidProvider
     */
    public function testCheckIntValueValid($value)
    {
        $this->assertSame((int)$value, $this->_checkIntValue($value));
    }

    public function intInvalidProvider()
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
            [[]],
        ];
    }

    /**
     * @dataProvider intInvalidProvider
     */
    public function testCheckIntValueInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkIntValue($value);
    }

    public function testCheckIntValueWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkIntValue('00', ['code' => 400]);
    }

    public function intValidRangeProvider()
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

    /**
     * @dataProvider intValidRangeProvider
     */
    public function testCheckIntValueValidRange($value, $min, $max)
    {
        $this->assertSame((int)$value, $this->_checkIntValue($value, ['min' => $min, 'max' => $max]));
    }

    public function intInvalidRangeProvider()
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

    /**
     * @dataProvider intInvalidRangeProvider
     */
    public function testCheckIntValueInvalidRange($value, $min, $max)
    {
        $this->expectException(RequestCheckException::class);
        $this->_checkIntValue($value, ['min' => $min, 'max' => $max]);
    }

    public function testCheckStringValid()
    {
        $this->assertSame('DeadBeef', $this->_checkString('DeadBeef'));
        $this->assertSame('DeadBeef', $this->_checkString(null, ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->_checkString('', ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->_checkString(' ', ['default' => 'DeadBeef']));
        $this->assertNull($this->_checkString(null, ['default' => null]));
        $this->assertNull($this->_checkString('', ['default' => null]));
        $this->assertNull($this->_checkString(' ', ['default' => null]));
    }

    public function stringInvalidProvider()
    {
        return [
            [null],
            [''],
            [' '],
            [['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider stringInvalidProvider
     */
    public function testCheckStringInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkString($value);
    }

    public function testCheckStringWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkString('', ['code' => 400]);
    }

    public function testCheckStringRegexValid()
    {
        $this->assertSame('DeadBeef', $this->_checkStringRegex('DeadBeef', '/^\w+$/sD'));
        $this->assertSame('dead@beef', $this->_checkStringRegex('dead@beef', '/@/'));
        $this->assertSame('DeadBeef', $this->_checkStringRegex(null, '/@/', ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->_checkStringRegex('', '/@/', ['default' => 'DeadBeef']));
        $this->assertSame('DeadBeef', $this->_checkStringRegex(' ', '/@/', ['default' => 'DeadBeef']));
        $this->assertSame('', $this->_checkStringRegex(null, '/@/', ['default' => '']));
        $this->assertSame('', $this->_checkStringRegex(' ', '/@/', ['default' => '']));
        $this->assertNull($this->_checkStringRegex(null, '/@/', ['default' => null]));
        $this->assertNull($this->_checkStringRegex(' ', '/@/', ['default' => null]));
    }

    /**
     * @dataProvider stringInvalidProvider
     */
    public function testCheckStringRegexInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkStringRegex($value, '/@/');
    }

    public function stringRegexUnmatchedProvider()
    {
        return [
            ['dead@beef', '/^\w+$/sD'],
            ['DeadBeef', '/@/'],
            [['foo' => 'dead@beef'], '/@/'],
        ];
    }

    /**
     * @dataProvider stringRegexUnmatchedProvider
     */
    public function testCheckStringRegexUnmatched($value, $regex)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkStringRegex($value, $regex);
    }

    public function testCheckStringRegexWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkStringRegex('', '/@/', ['code' => 400]);
    }

    public function testCheckIntValid()
    {
        $this->assertSame(0, $this->_checkInt('0'));
        $this->assertSame(1, $this->_checkInt('1'));
        $this->assertSame(-2147483648, $this->_checkInt('-2147483648'));
        $this->assertSame(2147483647, $this->_checkInt('2147483647'));
        $this->assertSame(0, $this->_checkInt(null, ['default' => 0]));
        $this->assertSame(1, $this->_checkInt('', ['default' => 1]));
        $this->assertSame(2, $this->_checkInt(' ', ['default' => 2]));
        $this->assertNull($this->_checkInt(null, ['default' => null]));
        $this->assertNull($this->_checkInt('', ['default' => null]));
        $this->assertNull($this->_checkInt(' ', ['default' => null]));
    }

    /**
     * @dataProvider intInvalidProvider
     */
    public function testCheckIntInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkInt($value);
    }

    public function intInvalidTypeProvider()
    {
        return [
            [null],
            [['foo' => '1']],
            [$this->_createUploadedFile()],
        ];
    }

    /**
     * @dataProvider intInvalidTypeProvider
     */
    public function testCheckIntInvalidType($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkInt($value);
    }

    public function testCheckIntInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkInt('', ['code' => 400]);
    }

    public function testCheckFloatValid()
    {
        $this->assertSame(0.0, $this->_checkFloat('0'));
        $this->assertSame(0.0, $this->_checkFloat(0));
        $this->assertSame(1.1, $this->_checkFloat('1.1'));
        $this->assertSame(1.1, $this->_checkFloat(1.1));
        $this->assertSame(1000000000.0, $this->_checkFloat('1e+9'));
        $this->assertSame(INF, $this->_checkFloat('1e+1000'));
        $this->assertSame(0.0, $this->_checkFloat(null, ['default' => 0.0]));
        $this->assertSame(1.0, $this->_checkFloat('', ['default' => 1.0]));
        $this->assertSame(2.0, $this->_checkFloat(' ', ['default' => 2.0]));
        $this->assertNull($this->_checkFloat(null, ['default' => null]));
        $this->assertNull($this->_checkFloat('', ['default' => null]));
        $this->assertNull($this->_checkFloat(' ', ['default' => null]));
    }

    public function floatInvalidProvider()
    {
        return [
            [null],
            [''],
            [' '],
            ['deadbeef'],
            ['1e'],
            ['1e+1e'],
            [['foo' => '1.0']],
            [$this->_createUploadedFile()],
        ];
    }

    /**
     * @dataProvider floatInvalidProvider
     */
    public function testCheckFloatInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkFloat($value);
    }

    public function floatValidRangeProvider()
    {
        return [
            ['1000000000', null, 1e+9],
            ['-1000000000', -1e+9, null],
            ['0', -1, 1],
        ];
    }

    /**
     * @dataProvider floatValidRangeProvider
     */
    public function testCheckFloatValidRange($value, $min, $max)
    {
        $this->assertSame((float)$value, $this->_checkFloat($value, ['min' => $min, 'max' => $max]));
    }

    public function floatInvalidRangeProvider()
    {
        return [
            ['1e+1000', null, PHP_FLOAT_MAX],
            ['-1000000000', -1e+8, null],
            ['1.0000000000001', -1, 1],
            [['foo' => '1.0'], 0, 1],
        ];
    }

    /**
     * @dataProvider floatInvalidRangeProvider
     */
    public function testCheckFloatInvalidRange($value, $min, $max)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkFloat($value, ['min' => $min, 'max' => $max]);
    }

    public function testCheckFloatInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkFloat(null, ['code' => 400]);
    }

    public function testCheckDateValid()
    {
        $date = Date::create(2020, 1, 2);
        $this->assertEquals($date, $this->_checkDate('20200102'));
        $this->assertEquals($date, $this->_checkDate('2020-01-02'));
        $this->assertEquals($date, $this->_checkDate('2020/01/02'));
        $this->assertEquals($date, $this->_checkDate('Jan 2 2020'));
        $this->assertSame($date, $this->_checkDate(null, ['default' => $date]));
        $this->assertSame($date, $this->_checkDate('', ['default' => $date]));
        $this->assertSame($date, $this->_checkDate(' ', ['default' => $date]));
        $this->assertEquals($date, $this->_checkDate($date));
        $this->assertEquals($date, $this->_checkDate(new DateTime('2020-01-02 01:01:01')));
        $this->assertEquals($date, $this->_checkDate(new DateTimeImmutable('2020-01-02 01:01:01')));
        $this->assertEquals($date, $this->_checkDate(Chronos::parse('2020-01-02 01:01:01')));
        $this->assertNull($this->_checkDate(null, ['default' => null]));
        $this->assertNull($this->_checkDate('', ['default' => null]));
        $this->assertNull($this->_checkDate(' ', ['default' => null]));
    }

    public function dateValidOptionsProvider()
    {
        return [
            ['20200102', ['formats' => 'Ymd']],
            ['2020-01-02', ['formats' => ['Ymd', 'Y-m-d']]],
            ['2020/01/02', ['formats' => ['Ymd', 'Y/m/d']]],
            ['01/02/2020', ['formats' => 'm/d/Y']],
            ['02/01/2020', ['formats' => 'd/m/Y']],
            ['Jan 2 2020', ['formats' => 'M j Y']],
            ['2020-01-02', ['min' => Date::create(2020, 1, 2)]],
            ['2020-01-02', ['min' => Date::create(2020, 1, 1)]],
            ['2020-01-02', ['max' => Date::create(2020, 1, 2)]],
            ['2020-01-02', ['max' => Date::create(2020, 1, 3)]],
            [Date::create(2020, 1, 2), ['formats' => 'Ymd']],
            [new DateTime('2020-01-02 01:01:01'), ['formats' => ['Y']]],
        ];
    }

    /**
     * @dataProvider dateValidOptionsProvider
     */
    public function testCheckDateValidFormats($value, $options)
    {
        $this->assertEquals(Date::create(2020, 1, 2), $this->_checkDate($value, $options));
    }

    public function dateInvalidProvider()
    {
        return [
            [''],
            [' '],
            ['deadbeef'],
            ['2020-13-01'],
            ['2020-12-32'],
            [['foo' => '2020-01-01']],
            [$this->_createUploadedFile()],
        ];
    }

    /**
     * @dataProvider dateInvalidProvider
     */
    public function testCheckDateInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkDate($value);
    }

    public function dateInvalidOptionsProvider()
    {
        $date = Date::create(2020, 1, 1);
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

    /**
     * @dataProvider dateInvalidOptionsProvider
     */
    public function testCheckDateInvalidFormat($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkDate($value, $options);
    }

    public function testCheckDateInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkDate(null, ['code' => 400]);
    }

    public function testCheckDateEmptyStringFormats()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->_checkDate(null, ['formats' => '']);
    }

    public function testCheckDateEmptyArrayFormats()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->_checkDate(null, ['formats' => []]);
    }

    public function testCheckDateWithClass()
    {
        RequestChecker\setDateClass('cakephp');
        $this->assertInstanceOf(FrozenDate::class, $this->_checkDate('today'));
    }

    public function testCheckArrayValid()
    {
        $this->assertSame(['foo'], $this->_checkArray(['foo']));
        $this->assertSame(['foo', 'bar'], $this->_checkArray(['foo', 'bar']));
        $this->assertSame(['foo'], $this->_checkArray(null, ['default' => ['foo']]));
        $this->assertSame(['bar'], $this->_checkArray('', ['default' => ['bar']]));
        $this->assertSame(['baz'], $this->_checkArray([], ['default' => ['baz']]));
        $this->assertSame([], $this->_checkArray([''], ['default' => []]));
        $this->assertNull($this->_checkArray(null, ['default' => null]));
        $this->assertNull($this->_checkArray('', ['default' => null]));
        $this->assertNull($this->_checkArray([], ['default' => null]));
        $this->assertNull($this->_checkArray([''], ['default' => null]));
    }

    public function arrayInvalidProvider()
    {
        return [
            [null],
            [''],
            [' '],
            [[]],
            [['']],
            ['deadbeef'],
            [$this->_createUploadedFile()],
        ];
    }

    /**
     * @dataProvider arrayInvalidProvider
     */
    public function testCheckArrayInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkArray($value);
    }

    public function arrayValidNumElementsProvider()
    {
        return [
            [['foo'], 1, null],
            [['foo', 'bar'], null, 2],
        ];
    }

    /**
     * @dataProvider arrayValidNumElementsProvider
     */
    public function testCheckArrayValidNumElements($value, $min, $max)
    {
        $this->assertSame($value, $this->_checkArray($value, ['minElements' => $min, 'maxElements' => $max]));
    }

    public function arrayInalidNumElementsProvider()
    {
        return [
            [['foo'], 2, null],
            [['foo', 'bar'], null, 1],
            [['foo', 'bar', 'baz'], 1, 2],
        ];
    }

    /**
     * @dataProvider arrayInalidNumElementsProvider
     */
    public function testCheckArrayInvalidNumElements($value, $min, $max)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkArray($value, ['minElements' => $min, 'maxElements' => $max]);
    }

    public function arrayValidValidatorProvider()
    {
        return [
            [['foo'], function ($v) { return $v === 'foo'; }],
            [['foo', 'bar'], function ($v) { return preg_match('/^\w+$/sD', $v); }],
        ];
    }

    /**
     * @dataProvider arrayValidValidatorProvider
     */
    public function testCheckArrayValidValidator($value, $validator)
    {
        $this->assertSame($value, $this->_checkArray($value, ['validator' => $validator]));
    }

    public function arrayInalidValidatorProvider()
    {
        return [
            [['fobar'], function ($v) { return is_numeric($v); }],
            [['foo', 'bar'], function ($v) { return preg_match('/^\d+$/sD', $v); }],
        ];
    }

    /**
     * @dataProvider arrayInalidValidatorProvider
     */
    public function testCheckArrayInvalidValidator($value, $validator)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkArray($value, ['validator' => $validator]);
    }

    public function testCheckArrayInvalidWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkArray(null, ['code' => 400]);
    }

    public function testCheckStringArrayValid()
    {
        $this->assertSame(['foo'], $this->_checkStringArray(['foo']));
        $this->assertSame(['foo', 'bar'], $this->_checkStringArray(['foo', 'bar']));
        $this->assertSame(['foo'], $this->_checkStringArray(null, ['default' => ['foo']]));
        $this->assertSame(['bar'], $this->_checkStringArray('', ['default' => ['bar']]));
        $this->assertSame(['baz'], $this->_checkStringArray([], ['default' => ['baz']]));
        $this->assertSame([], $this->_checkStringArray([''], ['default' => []]));
        $this->assertNull($this->_checkStringArray(null, ['default' => null]));
        $this->assertNull($this->_checkStringArray('', ['default' => null]));
        $this->assertNull($this->_checkStringArray([], ['default' => null]));
        $this->assertNull($this->_checkStringArray([''], ['default' => null]));
    }

    public function stringArrayValidOptionsProvider()
    {
        return [
            [['', ''], ['allowEmpty' => true]],
            [['foo', ''], ['allowEmpty' => true]],
            [['', ''], ['allowEmpty' => true, 'regex' => '/^\w+$/']],
            [['foo', ''], ['allowEmpty' => true, 'regex' => '/^\w+$/']],
            [['foo'], ['regex' => '/^\w+$/']],
            [['foo', 'bar'], ['regex' => '/^\w+$/']],
            [['100', '200'], ['validator' => function ($v) { return is_numeric($v); }]],
        ];
    }

    /**
     * @dataProvider stringArrayValidOptionsProvider
     */
    public function testCheckStringArrayValidOptions($value, $options)
    {
        $this->assertSame($value, $this->_checkStringArray($value, $options));
    }

    /**
     * @dataProvider arrayInvalidProvider
     */
    public function testCheckStringArrayInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkStringArray($value);
    }

    /**
     * @dataProvider stringInvalidProvider
     */
    public function testCheckStringArrayInvalidValue($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkStringArray([$value]);
    }

    public function stringArrayInvalidOptionsProvider()
    {
        return [
            [['foo', ''], ['allowEmpty' => true, 'regex' => '/^\d+$/']],
            [['foo', 'bar'], ['regex' => '/^\d+$/']],
            [['foo', 'bar'], ['validator' => function ($v) { return is_numeric($v); }]],
        ];
    }

    /**
     * @dataProvider stringArrayInvalidOptionsProvider
     */
    public function testCheckStringArrayInvalidOptions($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkStringArray($value, $options);
    }

    public function testCheckStringArrayWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkStringArray(null, ['code' => 400]);
    }

    public function testCheckIntArrayValid()
    {
        $this->assertSame([1], $this->_checkIntArray(['1']));
        $this->assertSame([1, 2], $this->_checkIntArray(['1', '2']));
        $this->assertSame([1], $this->_checkIntArray(null, ['default' => [1]]));
        $this->assertSame([2], $this->_checkIntArray('', ['default' => [2]]));
        $this->assertSame([1, 2], $this->_checkIntArray([], ['default' => [1, 2]]));
        $this->assertSame([], $this->_checkIntArray([''], ['default' => []]));
        $this->assertNull($this->_checkIntArray(null, ['default' => null]));
        $this->assertNull($this->_checkIntArray('', ['default' => null]));
        $this->assertNull($this->_checkIntArray([], ['default' => null]));
        $this->assertNull($this->_checkIntArray([''], ['default' => null]));
    }

    /**
     * @dataProvider arrayInvalidProvider
     */
    public function testCheckIntArrayInvalid($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkIntArray($value);
    }

    /**
     * @dataProvider stringInvalidProvider
     */
    public function testCheckIntArrayInvalidString($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkIntArray([$value]);
    }

    /**
     * @dataProvider intInvalidProvider
     */
    public function testCheckIntArrayInvalidInteger($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkIntArray([$value]);
    }

    /**
     * @dataProvider intInvalidTypeProvider
     */
    public function testCheckIntArrayInvalidType($value)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkIntArray([$value]);
    }

    public function intArrayValidOptionsProvider()
    {
        return [
            [[1, 2], ['1', '2'], ['min' => 0]],
            [[1, 2], ['1', '2'], ['max' => 10]],
            [[2, 4], ['2', '4'], ['validator' => function ($v) { return $v % 2 === 0; }]],
        ];
    }

    /**
     * @dataProvider intArrayValidOptionsProvider
     */
    public function testCheckIntArrayValidOptions($expected, $value, $options)
    {
        $this->assertSame($expected, $this->_checkIntArray($value, $options));
    }

    public function intArrayInvalidOptionsProvider()
    {
        return [
            [['1', '2'], ['min' => 10]],
            [['1', '2'], ['max' => 0]],
            [['2', '4'], ['validator' => function ($v) { return $v % 2 !== 0; }]],
        ];
    }

    /**
     * @dataProvider intArrayInvalidOptionsProvider
     */
    public function testCheckIntArrayInvalidOptions($value, $options)
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(404);
        $this->_checkIntArray($value, $options);
    }

    public function testCheckIntArrayWithCode()
    {
        $this->expectException(RequestCheckException::class);
        $this->expectExceptionCode(400);
        $this->_checkIntArray(null, ['code' => 400]);
    }
}
