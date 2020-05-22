<?php
declare(strict_types=1);

use Cake\Event\EventManager;
use RequestChecker\Middleware\RequestEncodingCheckerMiddleware;

EventManager::instance()->on('Server.buildMiddleware', function ($event, $queue) {
    $middleware = new RequestEncodingCheckerMiddleware();
    $queue->prepend($middleware);
});
