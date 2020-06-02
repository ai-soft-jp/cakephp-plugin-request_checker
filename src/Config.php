<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Chronos\ChronosInterface;
use Cake\Chronos\Date;
use Cake\I18n\FrozenDate;
use InvalidArgumentException;
use ReflectionClass;

/**
 * @internal
 */
class Config
{
    /**
     * Class name map
     */
    protected const CLASS_MAP = [
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
    protected static $dateClass = Date::class;

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
}
