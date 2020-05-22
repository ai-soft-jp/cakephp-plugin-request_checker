<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Chronos\Date;
use DateTimeInterface;
use Exception;
use RequestChecker\Exception\RequestCheckException;

trait RequestCheckerTrait
{
    /**
     * Default range for _checkIntValue
     */
    protected $_checkIntDefaultRange = [2147483647, -2147483648];

    /**
     * Check integer value
     *
     * @param string $value Integer value
     * @param int $code HTTP code
     * @param array|null|false $range [min, max]
     * @return int|null
     */
    protected function _checkIntValue(string $value, int $code = 404, $range = null): ?int
    {
        $intValue = (int)$value;
        if ((string)$intValue !== $value) {
            throw new RequestCheckException("Invalid integer value: {$value}", $code);
        }
        if ($range === null) {
            $range = $this->$_checkIntDefaultRange;
        }
        if ($range !== false && ($intValue > $range[0] || $intValue < $range[1])) {
            throw new RequestCheckException("Invalid integer value: {$value}", $code);
        }
        return $intValue;
    }

    /**
     * Check string value
     *
     * @param mixed $value String value
     * @param string|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return string|null
     */
    protected function _checkString($value, $default = false, int $code = 404): ?string
    {
        if ($value === null && $default !== false) {
            return $default;
        }
        if (!is_string($value)) {
            throw new RequestCheckException("Invalid string value", $code);
        }
        if (trim($value) === '') {
            if ($default === false) {
                throw new RequestCheckException("Invalid empty value", $code);
            } else {
                $value = $default;
            }
        }
        return $value;
    }

    /**
     * Check string value with regex
     *
     * @param mixed $value String value
     * @param string $regex Regular expression
     * @param string|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return string|null
     */
    protected function _checkStringRegex($value, string $regex, $default = false, int $code = 404): ?string
    {
        $value = $this->_checkString($value, $default, $code);
        if ($value === $default) {
            return $default;
        }
        if (!preg_match($regex, $value)) {
            throw new RequestCheckException("Invalid string value: {$value}", $code);
        }
        return $value;
    }

    /**
     * Check integer value
     *
     * @param mixed $value Integer value
     * @param int|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return int|null
     */
    protected function _checkInt($value, $default = false, int $code = 404): ?int
    {
        $value = $this->_checkString($value, $default, $code);
        if ($value === $default) {
            return $default;
        }
        return $this->_checkIntValue($value, $code);
    }

    /**
     * Check integer value (64-bit)
     *
     * @param mixed $value Integer value
     * @param int|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return int|null
     */
    protected function _checkInt64($value, $default = false, int $code = 404): ?int
    {
        $value = $this->_checkString($value, $default, $code);
        if ($value === $default) {
            return $default;
        }
        return $this->_checkIntValue($value, $code, false);
    }

    /**
     * Check float value
     *
     * @param mixed $value Float value
     * @param float|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return int|null
     */
    protected function _checkFloat($value, $default = false, int $code = 404): ?int
    {
        $value = $this->_checkString($value, $default, $code);
        if ($value === $default) {
            return $default;
        }
        if (!is_numeric($value)) {
            throw new RequestCheckException("Invalid float value: {$value}", $code);
        }
        return (float)$value;
    }

    /**
     * Check date value
     *
     * @param mixed $value Date value
     * @param \DateTimeInterface|null|false $default Default value (false = throw)
     * @param string|array|null $formats Assumed formats
     * @param int $code HTTP code
     * @return \Cake\Chronos\Date|null
     */
    protected function _checkDate($value, $default = false, $formats = null, int $code = 404): ?Date
    {
        if ($value instanceof DateTimeInterface) {
            return Date::instance($value);
        }
        if ($default && !($default instanceof Date)) {
            $default = Date::instance($default);
        }

        $value = $this->_checkString($value, $default, $code);
        if ($value === $default) {
            return $default;
        }

        if ($formats === null && ctype_digit($value)) {
            $formats = ['%Y%m%d'];
        }
        if ($formats === null) {
            try {
                $date = Date::parse($value);
            } catch (Exception $e) {
                throw new RequestCheckException("Invalid date value: {$value}", $code, $e);
            }
        } else {
            $p = false;
            foreach ((array)$formats as $format) {
                $p = strptime($value, $format);
                if ($p !== false) {
                    break;
                }
            }
            $date = null;
            if ($p !== false) {
                $year = $p['tm_year'] + 1900;
                $month = $p['tm_mon'] + 1;
                $mday = $p['tm_mday'];
                if (checkdate($month, $mday, $year)) {
                    $date = Date::create($year, $month, $mday);
                }
            }
            if ($date === null) {
                throw new RequestCheckException("Invalid date value: {$value}", $code);
            }
        }

        return $date;
    }

    /**
     * Check array
     *
     * @param mixed $value Array
     * @param array|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return array|null
     */
    protected function _checkArray($value, $default = false, int $code = 404): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            if ($default === false) {
                throw new RequestCheckException("Invalid empty array", $code);
            } else {
                return $default;
            }
        }
        if (!is_array($value)) {
            throw new RequestCheckException("Invalid array value", $code);
        }
        return $value;
    }

    /**
     * Check string array
     *
     * @param mixed $value String array
     * @param array|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return array|null
     */
    protected function _checkStringArray($value, $default = false, int $code = 404): ?array
    {
        $value = $this->_checkArray($value, $default, $code);
        if ($value === $default) {
            return $default;
        }
        array_walk($value, function ($elem) use ($default, $code) {
            if (!is_string($elem)) {
                throw new RequestCheckException("Invalid array element", $code);
            }
            if ($default !== false && $elem === '') {
                throw new RequestCheckException("Invalid blank element", $code);
            }
        });
        return $value;
    }

    /**
     * Check integer array
     *
     * @param mixed $value Integer array
     * @param array|null|false $default Default value (false = throw)
     * @param int $code HTTP code
     * @return array|null
     */
    protected function _checkIntArray($value, $default = false, int $code = 404): ?array
    {
        $value = $this->_checkArray($value, $default, $code);
        if ($value === $default) {
            return $default;
        }
        array_walk($value, function (&$elem) use ($code) {
            if (!is_string($elem)) {
                throw new RequestCheckException("Invalid array element", $code);
            }
            $elem = $this->_checkIntValue($elem, $code);
        });
        return $value;
    }
}
