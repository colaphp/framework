<?php

declare(strict_types=1);

namespace Flame\Container;

use DI\ContainerBuilder;
use Exception;

/**
 * @mixin \DI\Container
 */
class Container
{
    /**
     * @throws Exception
     */
    public static function instance()
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);

        return $builder->build();
    }

    /**
     * @throws Exception
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}
