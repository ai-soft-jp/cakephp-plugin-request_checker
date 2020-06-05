<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Chronos\ChronosInterface;
use Cake\Chronos\Date;
use Cake\I18n\FrozenDate;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use RequestChecker\Exception\RequestCheckException;
use Throwable;

class RequestChecker
{
    /**
     * Class name map
     */
    private const CLASS_MAP = [
        'chronos' => Date::class,
        'cakephp' => FrozenDate::class,
    ];

    /**
     * Default checkInt options
     */
    public const DEFAULT_INT_OPTIONS = [
        'min' => -2147483648,
        'max' => 2147483647,
    ];

    /**
     * Date class
     */
    private static $dateClass = Date::class;

    /**
     * Set class of Date
     *
     * @param string $class Class name
     * @return string
     */
    public static function setDateClass(string $class): string
    {
        if (isset(static::CLASS_MAP[$class])) {
            $class = static::CLASS_MAP[$class];
        }
        $classRef = new ReflectionClass($class);
        if (!$classRef->implementsInterface(ChronosInterface::class)) {
            throw new InvalidArgumentException("Class {$class} must implement Cake\\Chronos\\ChronosInterface.");
        }
        static::$dateClass = $class;
        return $class;
    }

    /**
     * Get class of Date
     *
     * @return string
     */
    public static function getDateClass(): string
    {
        return static::$dateClass;
    }

    /**
     * Throws RequestCheckException
     *
     * @param string $message Message
     * @param mixed $value Value
     * @param array $options Options
     * @param \Throwable|null $prev Previous exception
     * @return void
     */
    private static function _throwException(string $message, $value, array $options, ?Throwable $prev = null): void
    {
        if (is_array($value)) {
            $valueStr = '[';
            foreach (array_keys($value) as $i => $key) {
                if ($i >= 3) {
                    $valueStr .= ', ...';
                    break;
                }
                if ($i !== $key) {
                    $valueStr .= "[{$key}] => ";
                }
                $valueStr .= static::_formatValue($value[$key]);
            }
            $valueStr .= ']';
        } else {
            $valueStr = static::_formatValue($value);
        }
        throw new RequestCheckException("{$message}: {$valueStr}", $options['code'] ?? null, $prev);
    }

    /**
     * Format value
     *
     * @param mixed $value Value
     * @return string
     */
    private static function _formatValue($value): string
    {
        if ($value === null) {
            return 'null';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            return (string)$value;
        } elseif (is_string($value)) {
            if (mb_strlen($value) > 16) {
                $value = mb_substr($value, 0, 16) . '...';
            }
            return "\"{$value}\"";
        } else {
            return gettype($value);
        }
    }

    /**
     * Check integer value.
     *
     * Valid option keys are:
     *
     * - `min`: Allowed minimum value. Null means unlimited. Defaults to INT32_MIN.
     * - `max`: Allowed maximum value. Null means unlimited. Defaults to INT32_MAX.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param string $value Integer value.
     * @param array $options Options.
     * @return int|null
     */
    public static function checkIntValue(string $value, array $options = []): ?int
    {
        $intValue = (int)$value;
        if ((string)$intValue !== $value) {
            static::_throwException('Invalid integer value', $value, $options);
        }
        $options += static::DEFAULT_INT_OPTIONS;
        if (
            (isset($options['min']) && $intValue < $options['min']) ||
            (isset($options['max']) && $intValue > $options['max'])
        ) {
            static::_throwException('Integer value out of range', $value, $options);
        }
        return $intValue;
    }

    /**
     * Check string value.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value String value.
     * @param array $options Options.
     * @return string|null
     * @throws \RequestChceker\Exception\RequestCheckException
     */
    public static function checkStringValue($value, array $options = []): ?string
    {
        $allowEmpty = array_key_exists('default', $options);
        if ($value === null && $allowEmpty) {
            return null;
        }
        if (!is_string($value)) {
            static::_throwException('Invalid string value', $value, $options);
        }
        if (trim($value) === '') {
            if (!$allowEmpty) {
                static::_throwException('Invalid empty value', $value, $options);
            }
            return null;
        }
        return $value;
    }

    /**
     * Check string.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value String value.
     * @param array $options Options.
     * @return string|null
     */
    public static function checkString($value, array $options = []): ?string
    {
        $value = static::checkStringValue($value, $options);
        return $value ?? $options['default'] ?? null;
    }

    /**
     * Check string with regex.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value String value.
     * @param string $regex Regular expression.
     * @param array $options Options.
     * @return string|null
     */
    public static function checkStringRegex($value, string $regex, array $options = []): ?string
    {
        $value = static::checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        if (!preg_match($regex, $value)) {
            static::_throwException('Invalid string value', $value, $options);
        }
        return $value;
    }

    /**
     * Check integer.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `min`: Allowed minimum value. Null means unlimited. Defaults to INT32_MIN.
     * - `max`: Allowed maximum value. Null means unlimited. Defaults to INT32_MAX.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value Integer value.
     * @param array $options Options.
     * @return int|null
     */
    public static function checkInt($value, array $options = []): ?int
    {
        $value = static::checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        return static::checkIntValue($value, $options);
    }

    /**
     * Check float.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `min`: Allowed minimum value. Defaults to unlimited.
     * - `max`: Allowed maximum value. Defaults to unlimited.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value Float value.
     * @param array $options Options.
     * @return float|null
     */
    public static function checkFloat($value, array $options = []): ?float
    {
        $value = static::checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        if (!is_numeric($value)) {
            static::_throwException('Invalid float value', $value, $options);
        }
        $value = (float)$value;
        if (
            (isset($options['min']) && $value < $options['min']) ||
            (isset($options['max']) && $value > $options['max'])
        ) {
            static::_throwException('Float value out of range', $value, $options);
        }
        return $value;
    }

    /**
     * Check date.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `formats`: Allowed foramts.
     * - `min`: Allowed minimum date. Defaults to unlimited.
     * - `max`: Allowed maximum date. Defaults to unlimited.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value Date value.
     * @param array $options Options.
     * @return \Cake\Chronos\ChronosInterface|null
     */
    public static function checkDate($value, array $options = []): ?ChronosInterface
    {
        $class = static::getDateClass();
        $formats = $options['formats'] ?? null;

        if ($formats !== null && empty($formats)) {
            throw new InvalidArgumentException('formats must not be empty.');
        }
        if ($value instanceof $class) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return $class::instance($value);
        }

        $value = static::checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }

        if ($formats === null) {
            try {
                $date = $class::parse($value);
            } catch (Exception $e) {
                static::_throwException('Invalid date value', $value, $options, $e);
            }
        } else {
            $found = false;
            foreach ((array)$formats as $format) {
                $date = DateTimeImmutable::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                static::_throwException('Invalid date value', $value, $options);
            }
            $date = $class::instance($date);
        }
        if (
            (isset($options['min']) && $date < $options['min']) ||
            (isset($options['max']) && $date > $options['max'])
        ) {
            static::_throwException('Date value out of range', $value, $options);
        }

        return $date;
    }

    /**
     * Check array.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `minElements`: Minimum number of elements. Defaults to unlimited.
     * - `maxElements`: Maximum number of elements. Defaults to unlimited.
     * - `validator`: Callable to validate each element.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value Array.
     * @param array $options Options.
     * @return array|null
     */
    public static function checkArray($value, array $options = []): ?array
    {
        $allowEmpty = array_key_exists('default', $options);
        if ($value === null || $value === '' || $value === [] || $value === ['']) {
            if (!$allowEmpty) {
                static::_throwException('Invalid empty array', $value, $options);
            }
            return $options['default'] ?? null;
        }
        if (!is_array($value)) {
            static::_throwException('Invalid array value', $value, $options);
        }
        $count = count($value);
        if (
            (isset($options['minElements']) && $count < $options['minElements']) ||
            (isset($options['maxElements']) && $count > $options['maxElements'])
        ) {
            static::_throwException('Array size out of range', $count, $options);
        }
        if (isset($options['validator'])) {
            $validator = $options['validator'];
            array_walk($value, function (&$elem, $key) use ($validator, $options) {
                if (!$validator($elem, $key)) {
                    static::_throwException('Invalid array element', $elem, $options);
                }
            });
        }
        return $value;
    }

    /**
     * Merge validator callable.
     *
     * @param array &$options Options.
     * @param callable $validator Primary validator callable.
     * @return void
     */
    private static function _checkMergeValidator(array &$options, callable $validator): void
    {
        if (isset($options['validator'])) {
            $prevValidator = $options['validator'];
            $options['validator'] = function (&$elem, $key) use ($validator, $prevValidator) {
                return $validator($elem, $key) && $prevValidator($elem, $key);
            };
        } else {
            $options['validator'] = $validator;
        }
    }

    /**
     * Check string array.
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `allowEmpty`: Allows empty element or not. Defaults to false.
     * - `minElements`: Minimum number of elements. Defaults to unlimited.
     * - `maxElements`: Maximum number of elements. Defaults to unlimited.
     * - `regex`: Regular expression to validate element.
     * - `validator`: Callable to validate each element.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value String array.
     * @param array $options Options.
     * @return array|null
     */
    public static function checkStringArray($value, array $options = []): ?array
    {
        $allowEmpty = $options['allowEmpty'] ?? false;
        $regex = $options['regex'] ?? null;
        if ($allowEmpty) {
            if ($regex) {
                $validator = function ($elem) use ($regex) {
                    return is_string($elem) && ($elem === '' || preg_match($regex, $elem));
                };
            } else {
                $validator = function ($elem) {
                    return is_string($elem);
                };
            }
        } else {
            if ($regex) {
                $validator = function ($elem) use ($regex) {
                    return is_string($elem) && trim($elem) !== '' && preg_match($regex, $elem);
                };
            } else {
                $validator = function ($elem) {
                    return is_string($elem) && trim($elem) !== '';
                };
            }
        }
        static::_checkMergeValidator($options, $validator);
        $value = static::checkArray($value, $options);
        return $value ?? $options['default'] ?? null;
    }

    /**
     * Check integer array
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `min`: Allowed minimum value. Null means unlimited. Defaults to INT32_MIN.
     * - `max`: Allowed maximum value. Null means unlimited. Defaults to INT32_MAX.
     * - `minElements`: Minimum number of elements. Defaults to unlimited.
     * - `maxElements`: Maximum number of elements. Defaults to unlimited.
     * - `validator`: Callable to validate each element.
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value Integer array.
     * @param array $options Options.
     * @return array|null
     */
    public static function checkIntArray($value, array $options = []): ?array
    {
        $validator = function (&$elem) use ($options) {
            if (!is_string($elem)) {
                return false;
            }
            $elem = static::checkIntValue($elem, $options);
            return true;
        };
        static::_checkMergeValidator($options, $validator);
        $value = static::checkArray($value, $options);
        return $value ?? $options['default'] ?? null;
    }
}
