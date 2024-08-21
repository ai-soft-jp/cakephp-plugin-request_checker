<?php
declare(strict_types=1);

namespace RequestChecker;

use Cake\Core\BasePlugin;

/**
 * Plugin class
 */
class Plugin extends BasePlugin
{
    /** The name of this plugin */
    protected ?string $name = 'RequestChecker';

    /** Don't try to load routes */
    protected bool $routesEnabled = false;
}
