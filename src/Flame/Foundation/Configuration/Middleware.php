<?php

declare(strict_types=1);

namespace Flame\Foundation\Configuration;

use Flame\Support\Facade\Log;
use RuntimeException;

class Middleware
{
    protected static array $instances = [];

    public static function load($allMiddlewares): void
    {
        if (! is_array($allMiddlewares)) {
            return;
        }
        foreach ($allMiddlewares as $appName => $middlewares) {
            if (! is_array($middlewares)) {
                throw new RuntimeException('Bad middleware config');
            }
            foreach ($middlewares as $className) {
                if (method_exists($className, 'process')) {
                    static::$instances[$appName][] = [$className, 'process'];
                } else {
                    $log = "middleware $className::process not exsits\n";
                    echo $log;
                    Log::error($log);
                }
            }
        }
    }

    public static function getMiddleware(string $appName, bool $withGlobalMiddleware = true)
    {
        $globalMiddleware = static::$instances['']['@'] ?? [];
        $appGlobalMiddleware = $withGlobalMiddleware && isset(static::$instances['']) ? static::$instances[''] : [];
        if ($appName === '') {
            return array_reverse(array_merge($globalMiddleware, $appGlobalMiddleware));
        }
        $appMiddleware = static::$instances[$appName] ?? [];

        return array_reverse(array_merge($globalMiddleware, $appGlobalMiddleware, $appMiddleware));
    }
}
