<?php

namespace Swift\Container;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Class Container
 * @package Swift\Container
 * @method static mixed get($name)
 * @method static mixed make($name, array $parameters)
 * @method static bool has($name)
 */
class Container
{
    /**
     * @var ContainerInterface
     */
    protected static $_instance = null;

    /**
     * @return ContainerInterface
     */
    public static function instance()
    {
        if (!static::$_instance) {
            $app = new ContainerBuilder();
            $app->useAutowiring(true);
            $app->useAnnotations(true);
            static::$_instance = $app->build();
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