<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Chronos\ChronosDate;

trait RequestCheckerTrait
{
    /**
     * RequestChecker instance
     */
    protected ?RequestChecker $_requestChecker;

    /**
     * Set RequestChecker instance
     *
     * @param \RequestChecker\RequestChecker|null $checker RequestChecker
     * @return \RequestChecker\RequestChecker
     */
    protected function setRequestChecker(?RequestChecker $checker = null): RequestChecker
    {
        return $this->_requestChecker = $checker ?? new RequestChecker();
    }

    /**
     * Get RequestChecker instance
     *
     * @return \RequestChecker\RequestChecker|null
     */
    protected function getRequestChecker(): ?RequestChecker
    {
        return $this->_requestChecker;
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
     * @param mixed $value Integer value.
     * @param array $options Options.
     * @return int|null
     */
    protected function _checkIntValue($value, array $options = []): ?int
    {
        return $this->_requestChecker->checkIntValue($value, $options);
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
     */
    protected function _checkStringValue($value, array $options = []): ?string
    {
        return $this->_requestChecker->checkStringValue($value, $options);
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
        return $this->_requestChecker->checkString($value, $options);
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
        return $this->_requestChecker->checkStringRegex($value, $regex, $options);
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
        return $this->_requestChecker->checkInt($value, $options);
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
        return $this->_requestChecker->checkFloat($value, $options);
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
     * - `code`: Exception code. Defaults to 404.
     *
     * @param mixed $value JSON value.
     * @param array $options Options.
     * @return array|object|null
     */
    protected function _checkJSON($value, array $options = [])
    {
        return $this->_requestChecker->checkJSON($value, $options);
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
     * @return \Cake\Chronos\ChronosDate|null
     */
    protected function _checkDate($value, array $options = []): ?ChronosDate
    {
        return $this->_requestChecker->checkDate($value, $options);
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
        return $this->_requestChecker->checkArray($value, $options);
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
        return $this->_requestChecker->checkStringArray($value, $options);
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
        return $this->_requestChecker->checkIntArray($value, $options);
    }
}
