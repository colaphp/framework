<?php

namespace Swift\Container;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Swift\Contracts\Bootstrap;
use Workerman\Worker;

/**
 * Class ContainerProvider
 * @package Swift\Container
 * @method static mixed get($name)
 * @method static mixed make($name, array $parameters)
 * @method static bool has($name)
 */
class ContainerProvider implements Bootstrap
{
    /**
     * @var ContainerInterface
     */
    protected static $_instance = null;

    /**
     * @param Worker $worker
     * @return void
     */
    public static function start($worker)
    {
        $app = new ContainerBuilder();

        $app->useAutowiring(true);
        $app->useAnnotations(true);

        static::$_instance = $app->build();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::$_instance->{$name}(... $arguments);
    }

    /**
     * @return ContainerInterface|null
     */
    public static function instance()
    {
        return static::$_instance;
    }
}