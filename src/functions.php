<?php
declare(strict_types=1);

namespace RequestChecker;

/**
 * Set class of Date
 *
 * @param string $class Class name
 * @return string
 */
function setDateClass(string $class): string
{
    return Config::setDateClass($class);
}

/**
 * Get class of Date
 *
 * @return string
 */
function getDateClass(): string
{
    return Config::getDateClass();
}
