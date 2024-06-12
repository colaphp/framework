<?php

declare(strict_types=1);

namespace Flame\Cache;

use Flame\Redis\RedisManager;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * @method static mixed get($key, $default = null)
 * @method static bool set($key, $value, $ttl = null)
 * @method static bool delete($key)
 * @method static bool clear()
 * @method static iterable getMultiple($keys, $default = null)
 * @method static bool setMultiple($values, $ttl = null)
 * @method static bool deleteMultiple($keys)
 * @method static bool has($key)
 */
class Cache
{
    public static ?Psr16Cache $instance = null;

    public static function instance(): Psr16Cache
    {
        if (! static::$instance) {
            $adapter = new RedisAdapter(RedisManager::connection()->client());
            self::$instance = new Psr16Cache($adapter);
        }

        return static::$instance;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}
