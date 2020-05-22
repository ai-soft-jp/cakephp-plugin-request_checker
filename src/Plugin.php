<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Core\BasePlugin;

/**
 * Plugin class
 */
class Plugin extends BasePlugin
{
    /**
     * Plugin name.
     *
     * @var string
     */
    protected $name = 'RequestChecker';

    /**
     * Don't try to load routes.
     *
     * @var bool
     */
    protected $routesEnabled = false;
}
