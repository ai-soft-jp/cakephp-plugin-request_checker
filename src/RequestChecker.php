<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Chronos\ChronosDate;
use Cake\I18n\Date;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use RequestChecker\Exception\RequestCheckException;
use Throwable;

class RequestChecker
{
    /**
     * Global default date class
     */
    private static string $_defaultDateClass = 'chronos';

    /**
     * Global default instance
     */
    private static self $_defaultInstance;

    /**
     * Class name map
     */
    private const CLASS_MAP = [
        'chronos' => ChronosDate::class,
        'cakephp' => Date::class,
    ];

    /**
     * Default options
     */
    private array $_defaultOptions = [
        'int' => [
            'min' => -2147483648,
            'max' => 2147483647,
        ],
        'json' => [
            'assoc' => true,
            'depth' => 512,
            'options' => 0,
        ],
    ];

    /**
     * Default exception code
     */
    private int $_defaultCode = 404;

    /**
     * Date class
     */
    private string $_dateClass;

    /**
     * Set the global default date class
     */
    public static function setDefaultDateClass(string $dateClass): void
    {
        self::$_defaultDateClass = $dateClass;
    }

    /**
     * Get the global default date class
     */
    public static function getDefaultDateClass(): string
    {
        return self::$_defaultDateClass;
    }

    /**
     * Retrieve the default instance
     */
    public static function defaultInstance(): self
    {
        self::$_defaultInstance ??= new self();
        return self::$_defaultInstance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setDateClass(self::$_defaultDateClass);
    }

    /**
     * Set default options
     *
     * @param string $type Option type
     * @param array $options Options
     * @return void
     */
    public function setDefaultOptions(string $type, array $options): void
    {
        if (!isset($this->_defaultOptions[$type])) {
            throw new InvalidArgumentException("Unexpected type {$type}.");
        }
        $this->_defaultOptions[$type] = $options;
    }

    /**
     * Get default options
     *
     * @param string $type Option type
     * @return array
     */
    public function getDefaultOptions(string $type): array
    {
        if (!isset($this->_defaultOptions[$type])) {
            throw new InvalidArgumentException("Unexpected type {$type}.");
        }
        return $this->_defaultOptions[$type];
    }

    /**
     * Set default exception code
     *
     * @param int $code Default exception code
     * @return void
     */
    public function setDefaultCode(int $code): void
    {
        $this->_defaultCode = $code;
    }

    /**
     * Get default exception code
     *
     * @return int
     */
    public function getDefaultCode(): int
    {
        return $this->_defaultCode;
    }

    /**
     * Set class of Date
     *
     * @param string $class Class name
     * @return string
     */
    public function setDateClass(string $class): string
    {
        if (isset(static::CLASS_MAP[$class])) {
            $class = static::CLASS_MAP[$class];
        }
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist.");
        }
        $this->_dateClass = $class;
        return $class;
    }

    /**
     * Get class of Date
     *
     * @return string
     */
    public function getDateClass(): string
    {
        return $this->_dateClass;
    }

    /**
     * Throws RequestCheckException
     *
     * @param string $message Message
     * @param mixed $value Value
     * @param array $options Options
     * @param \Throwable|null $prev Previous exception
     * @return never
     */
    private function _throwException(string $message, mixed $value, array $options, ?Throwable $prev = null): never
    {
        if (is_array($value)) {
            $valueStr = '[';
            foreach (array_keys($value) as $i => $key) {
                if ($i >= 3) {
                    $valueStr .= ', ...';
                    break;
                }
                if ($i > 0) {
                    $valueStr .= ', ';
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
        $code = $options['code'] ?? $this->_defaultCode;
        throw new RequestCheckException("{$message}: {$valueStr}", $code, $prev);
    }

    /**
     * Format value
     *
     * @param mixed $value Value
     * @return string
     */
    private function _formatValue(mixed $value): string
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
     * - `code`: Exception code.
     *
     * @param mixed $value Integer value.
     * @param array $options Options.
     * @return int|null
     */
    public function checkIntValue(mixed $value, array $options = []): ?int
    {
        if (is_int($value)) {
            $intValue = $value;
        } elseif (is_string($value)) {
            $intValue = (int)$value;
            if ((string)$intValue !== $value) {
                $this->_throwException('Invalid integer value', $value, $options);
            }
        } else {
            $this->_throwException('Invalid string value', $value, $options);
        }
        $options += $this->_defaultOptions['int'];
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
     * - `code`: Exception code.
     *
     * @param mixed $value String value.
     * @param array $options Options.
     * @return string|null
     * @throws \RequestChecker\Exception\RequestCheckException
     */
    public function checkStringValue(mixed $value, array $options = []): ?string
    {
        $allowEmpty = array_key_exists('default', $options);
        if ($value === null && $allowEmpty) {
            return null;
        }
        if (!is_string($value)) {
            $this->_throwException('Invalid string value', $value, $options);
        }
        if (trim($value) === '') {
            if (!$allowEmpty) {
                $this->_throwException('Invalid empty value', $value, $options);
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
     * - `code`: Exception code.
     *
     * @param mixed $value String value.
     * @param array $options Options.
     * @return string|null
     */
    public function checkString(mixed $value, array $options = []): ?string
    {
        $value = $this->checkStringValue($value, $options);
        return $value ?? $options['default'] ?? null;
    }

    /**
     * Check string with regex.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `code`: Exception code.
     *
     * @param mixed $value String value.
     * @param string $regex Regular expression.
     * @param array $options Options.
     * @return string|null
     */
    public function checkStringRegex(mixed $value, string $regex, array $options = []): ?string
    {
        $value = $this->checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        if (!preg_match($regex, $value)) {
            $this->_throwException('Invalid string value', $value, $options);
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
     * - `code`: Exception code.
     *
     * @param mixed $value Integer value.
     * @param array $options Options.
     * @return int|null
     */
    public function checkInt(mixed $value, array $options = []): ?int
    {
        if (!is_int($value)) {
            $value = $this->checkStringValue($value, $options);
            if ($value === null) {
                return $options['default'] ?? null;
            }
        }
        return $this->checkIntValue($value, $options);
    }

    /**
     * Check float.
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `min`: Allowed minimum value. Defaults to unlimited.
     * - `max`: Allowed maximum value. Defaults to unlimited.
     * - `code`: Exception code.
     *
     * @param mixed $value Float value.
     * @param array $options Options.
     * @return float|null
     */
    public function checkFloat(mixed $value, array $options = []): ?float
    {
        if (is_int($value)) {
            $value = (float)$value;
        } elseif (!is_float($value)) {
            $value = $this->checkStringValue($value, $options);
            if ($value === null) {
                return $options['default'] ?? null;
            }
            if (!is_numeric($value)) {
                $this->_throwException('Invalid float value', $value, $options);
            }
            $value = (float)$value;
        }
        if (
            (isset($options['min']) && $value < $options['min']) ||
            (isset($options['max']) && $value > $options['max'])
        ) {
            $this->_throwException('Float value out of range', $value, $options);
        }
        return $value;
    }

    /**
     * Check JSON
     *
     * Valid option keys are:
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `assoc`: Returns association array rather than object. Defaults to true.
     * - `depth`: Maximum depth. Defaults to 512.
     * - `options`: json_decode() options. Defaults to nothing.
     * - `code`: Exception code.
     *
     * @param mixed $value JSON value.
     * @param array $options Options.
     * @return mixed
     */
    public function checkJSON(mixed $value, array $options = []): mixed
    {
        $value = $this->checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        $options += $this->_defaultOptions['json'];
        $jsonValue = json_decode($value, $options['assoc'], $options['depth'], $options['options']);
        if ($jsonValue === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->_throwException('Invalid JSON value', $value, $options);
        }
        return $jsonValue;
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
     * - `code`: Exception code.
     *
     * @param mixed $value Date value.
     * @param array $options Options.
     * @return \Cake\Chronos\ChronosDate|null
     */
    public function checkDate(mixed $value, array $options = []): ?ChronosDate
    {
        $dateClass = $this->getDateClass();
        $formats = $options['formats'] ?? null;

        if ($formats !== null && empty($formats)) {
            throw new InvalidArgumentException('formats must not be empty.');
        }
        if ($value instanceof $dateClass) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return new $dateClass($value);
        }

        $value = $this->checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }

        if ($formats === null) {
            try {
                $date = new $dateClass($value);
            } catch (Exception $e) {
                $this->_throwException('Invalid date value', $value, $options, $e);
            }
        } else {
            $found = false;
            foreach ((array)$formats as $format) {
                try {
                    $date = $dateClass::createFromFormat($format, $value);
                    if ($date !== false && $date->format($format) === $value) {
                        $found = true;
                        break;
                    }
                } catch (InvalidArgumentException $e) {
                    // continue to next format
                }
            }
            if (!$found) {
                static::_throwException('Invalid date value', $value, $options);
            }
        }
        if (
            (isset($options['min']) && $date < $options['min']) ||
            (isset($options['max']) && $date > $options['max'])
        ) {
            $this->_throwException('Date value out of range', $value, $options);
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
     * - `checkString`: Checks each element is string or not. Defaults to true.
     * - `converter`: Callable to convert each element. Called before validator.
     * - `validator`: Callable to validate each element.
     * - `code`: Exception code.
     *
     * @param mixed $value Array.
     * @param array $options Options.
     * @return array|null
     */
    public function checkArray(mixed $value, array $options = []): ?array
    {
        $allowEmpty = array_key_exists('default', $options);
        if ($value === null || $value === '' || $value === [] || $value === ['']) {
            if (!$allowEmpty) {
                $this->_throwException('Invalid empty array', $value, $options);
            }
            return $options['default'] ?? null;
        }
        if (!is_array($value)) {
            $this->_throwException('Invalid array value', $value, $options);
        }
        $count = count($value);
        if (
            (isset($options['minElements']) && $count < $options['minElements']) ||
            (isset($options['maxElements']) && $count > $options['maxElements'])
        ) {
            $this->_throwException('Array size out of range', $count, $options);
        }

        $checkString = $options['checkString'] ?? true;
        $converter = $options['converter'] ?? null;
        $validator = $options['validator'] ?? null;
        $resultValue = [];
        foreach ($value as $key => $elem) {
            if ($checkString && !is_string($elem)) {
                $this->_throwException('Invalid array element', $elem, $options);
            }
            if ($converter) {
                $elem = $converter($elem);
            }
            if ($validator && !$validator($elem)) {
                $this->_throwException('Invalid array element', $elem, $options);
            }
            $resultValue[$key] = $elem;
        }
        return $resultValue;
    }

    /**
     * Check string array.
     *
     * - `default`: Default value. Throws exception if not exists.
     * - `minElements`: Minimum number of elements. Defaults to unlimited.
     * - `maxElements`: Maximum number of elements. Defaults to unlimited.
     * - `allowEmpty`: Allows empty element or not. Defaults to false.
     * - `converter`: Callable to convert each element. Called before validator.
     * - `regex`: Regular expression to validate element.
     * - `validator`: Callable to validate each element. `regex` will be ignored if exists.
     * - `code`: Exception code.
     *
     * @param mixed $value String array.
     * @param array $options Options.
     * @return array|null
     */
    public function checkStringArray(mixed $value, array $options = []): ?array
    {
        $options['checkString'] = true;
        $allowEmpty = (bool)($options['allowEmpty'] ?? false);
        $validator = $options['validator'] ?? null;
        if ($validator === null && isset($options['regex'])) {
            $regex = $options['regex'];
            $validator = function ($elem) use ($regex) {
                return preg_match($regex, $elem);
            };
        }
        $options['validator'] = function ($elem) use ($allowEmpty, $validator) {
            if (trim($elem) === '') {
                return $allowEmpty;
            }
            if ($validator && !$validator($elem)) {
                return false;
            }
            return true;
        };
        $value = $this->checkArray($value, $options);
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
     * - `code`: Exception code.
     *
     * @param mixed $value Integer array.
     * @param array $options Options.
     * @return array|null
     */
    public function checkIntArray(mixed $value, array $options = []): ?array
    {
        $options['checkString'] = false;
        $options['converter'] = function ($elem) use ($options) {
            return $this->checkIntValue($elem, $options);
        };
        $value = $this->checkArray($value, $options);
        return $value ?? $options['default'] ?? null;
    }
}
