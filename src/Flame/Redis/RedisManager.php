<?php

declare(strict_types=1);

namespace Flame\Redis;

use Illuminate\Events\Dispatcher;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\RedisManager as IlluminateRedisManager;
use Workerman\Timer;
use Workerman\Worker;

/**
 * @mixin PhpRedisConnection
 */
class RedisManager
{
    protected static ?IlluminateRedisManager $instance = null;

    public static function instance(): ?IlluminateRedisManager
    {
        if (is_null(static::$instance)) {
            $config = config('redis');

            static::$instance = new IlluminateRedisManager(config('app.name'), 'phpredis', $config);
        }

        return static::$instance;
    }

    public static function connection(string $name = 'default'): Connection
    {
        static $timers = [];
        $connection = static::instance()->connection($name);
        if (! isset($timers[$name])) {
            $timers[$name] = Worker::getAllWorkers() ? Timer::add(55, function () use ($connection) {
                $connection->get('ping');
            }) : 1;
            if (class_exists(Dispatcher::class)) {
                $connection->setEventDispatcher(new Dispatcher());
            }
        }

        return $connection;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return static::connection()->{$name}(...$arguments);
    }
}
