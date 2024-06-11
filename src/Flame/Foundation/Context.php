<?php

declare(strict_types=1);

namespace Flame\Foundation;

use Fiber;
use SplObjectStorage;
use StdClass;
use Swow\Coroutine;
use WeakMap;
use Workerman\Events\Revolt;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;
use Workerman\Worker;

class Context
{
    /**
     * @var SplObjectStorage|WeakMap
     */
    protected static $objectStorage;

    /**
     * @var StdClass
     */
    protected static $object;

    protected static function getObject(): StdClass
    {
        if (! static::$objectStorage) {
            static::$objectStorage = class_exists(WeakMap::class) ? new WeakMap() : new SplObjectStorage();
            static::$object = new StdClass;
        }
        $key = static::getKey();
        if (! isset(static::$objectStorage[$key])) {
            static::$objectStorage[$key] = new StdClass;
        }

        return static::$objectStorage[$key];
    }

    /**
     * @return mixed
     */
    protected static function getKey()
    {
        switch (Worker::$eventLoopClass) {
            case Revolt::class:
                return Fiber::getCurrent();
            case Swoole::class:
                return \Swoole\Coroutine::getContext();
            case Swow::class:
                return Coroutine::getCurrent();
        }

        return static::$object;
    }

    /**
     * @return mixed
     */
    public static function get(?string $key = null)
    {
        $obj = static::getObject();
        if ($key === null) {
            return $obj;
        }

        return $obj->$key ?? null;
    }

    public static function set(string $key, $value): void
    {
        $obj = static::getObject();
        $obj->$key = $value;
    }

    public static function delete(string $key): void
    {
        $obj = static::getObject();
        unset($obj->$key);
    }

    public static function has(string $key): bool
    {
        $obj = static::getObject();

        return property_exists($obj, $key);
    }

    public static function destroy(): void
    {
        unset(static::$objectStorage[static::getKey()]);
    }
}
