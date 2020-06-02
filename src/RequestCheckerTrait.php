<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Chronos\ChronosInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use RequestChecker\Exception\RequestCheckException;

trait RequestCheckerTrait
{
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
    protected function _checkIntValue(string $value, array $options = []): ?int
    {
        $intValue = (int)$value;
        if ((string)$intValue !== $value) {
            throw new RequestCheckException("Invalid integer value: {$value}", $options['code'] ?? null);
        }
        $options += Config::DEFAULT_INT_OPTIONS;
        if (
            (isset($options['min']) && $intValue < $options['min']) ||
            (isset($options['max']) && $intValue > $options['max'])
        ) {
            throw new RequestCheckException("Integer value out of range: {$value}", $options['code'] ?? null);
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
    protected function _checkStringValue($value, array $options = []): ?string
    {
        $allowEmpty = array_key_exists('default', $options);
        if ($value === null && $allowEmpty) {
            return null;
        }
        if (!is_string($value)) {
            throw new RequestCheckException('Invalid string value', $options['code'] ?? null);
        }
        if (trim($value) === '') {
            if (!$allowEmpty) {
                throw new RequestCheckException('Invalid empty value', $options['code'] ?? null);
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
    protected function _checkString($value, array $options = []): ?string
    {
        $value = $this->_checkStringValue($value, $options);
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
    protected function _checkStringRegex($value, string $regex, array $options = []): ?string
    {
        $value = $this->_checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        if (!preg_match($regex, $value)) {
            throw new RequestCheckException("Invalid string value: {$value}", $options['code'] ?? null);
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
    protected function _checkInt($value, array $options = []): ?int
    {
        $value = $this->_checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        return $this->_checkIntValue($value, $options);
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
    protected function _checkFloat($value, array $options = []): ?float
    {
        $value = $this->_checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }
        if (!is_numeric($value)) {
            throw new RequestCheckException("Invalid float value: {$value}", $options['code'] ?? null);
        }
        $value = (float)$value;
        if (
            (isset($options['min']) && $value < $options['min']) ||
            (isset($options['max']) && $value > $options['max'])
        ) {
            throw new RequestCheckException("Float value out of range: {$value}", $options['code'] ?? null);
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
    protected function _checkDate($value, array $options = []): ?ChronosInterface
    {
        $class = Config::getDateClass();
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

        $value = $this->_checkStringValue($value, $options);
        if ($value === null) {
            return $options['default'] ?? null;
        }

        if ($formats === null) {
            try {
                $date = $class::parse($value);
            } catch (Exception $e) {
                throw new RequestCheckException("Invalid date value: {$value}", $options['code'] ?? null, $e);
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
                throw new RequestCheckException("Invalid date value: {$value}", $options['code'] ?? null);
            }
            $date = $class::instance($date);
        }
        if (
            (isset($options['min']) && $date < $options['min']) ||
            (isset($options['max']) && $date > $options['max'])
        ) {
            throw new RequestCheckException("Date value out of range: {$value}", $options['code'] ?? null);
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
    protected function _checkArray($value, array $options = []): ?array
    {
        $allowEmpty = array_key_exists('default', $options);
        if ($value === null || $value === '' || $value === [] || $value === ['']) {
            if (!$allowEmpty) {
                throw new RequestCheckException('Invalid empty array', $options['code'] ?? null);
            }
            return $options['default'] ?? null;
        }
        if (!is_array($value)) {
            throw new RequestCheckException('Invalid array value', $options['code'] ?? null);
        }
        $count = count($value);
        if (
            (isset($options['minElements']) && $count < $options['minElements']) ||
            (isset($options['maxElements']) && $count > $options['maxElements'])
        ) {
            throw new RequestCheckException('Array size out of range', $options['code'] ?? null);
        }
        if (isset($options['validator'])) {
            $validator = $options['validator'];
            array_walk($value, function (&$elem, $key) use ($validator, $options) {
                if (!$validator($elem, $key)) {
                    throw new RequestCheckException('Invalid array element', $options['code'] ?? null);
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
    private function _checkMergeValidator(array &$options, callable $validator): void
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
    protected function _checkStringArray($value, array $options = []): ?array
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
        $this->_checkMergeValidator($options, $validator);
        $value = $this->_checkArray($value, $options);
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
    protected function _checkIntArray($value, array $options = []): ?array
    {
        $validator = function (&$elem) use ($options) {
            if (!is_string($elem)) {
                return false;
            }
            $elem = $this->_checkIntValue($elem, $options);
            return true;
        };
        $this->_checkMergeValidator($options, $validator);
        $value = $this->_checkArray($value, $options);
        return $value ?? $options['default'] ?? null;
    }
}
