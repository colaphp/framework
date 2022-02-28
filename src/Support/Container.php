<?php

namespace Swift\Support;

use Psr\Container\ContainerInterface;
use Swift\Container\Container as BaseContainer;

/**
 * Class Container
 * @package Swift\Support
 * @method static mixed get($name)
 * @method static mixed make($name, array $parameters)
 * @method static bool has($name)
 */
class Container
{
    /**
     * @var ContainerInterface
     */
    protected static $_instance;

    /**
     * @return ContainerInterface
     */
    public static function instance()
    {
        if (!static::$_instance) {
            static::$_instance = new BaseContainer();
        }
        return static::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}
