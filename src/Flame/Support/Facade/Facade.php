<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

abstract class Facade
{
    /**
     * 始终创建新的对象实例
     */
    protected static bool $alwaysNewInstance = true;

    protected static array $instance = [];

    /**
     * 获取当前Facade对应类名
     */
    protected static function getFacadeClass(): string
    {
        return '';
    }

    /**
     * 创建Facade实例
     */
    protected static function createFacade()
    {
        $class = static::getFacadeClass();

        if (static::$alwaysNewInstance) {
            return new $class();
        }

        if (! isset(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }

        return self::$instance[$class];
    }

    /**
     * 获取实例
     */
    public static function getInstance()
    {
        $class = static::getFacadeClass();

        if (isset(self::$instance[$class])) {
            return self::$instance[$class];
        }

        return self::createFacade();
    }

    /**
     * 调用实际类的方法
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}
