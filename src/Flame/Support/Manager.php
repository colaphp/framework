<?php

declare(strict_types=1);

namespace Flame\Support;

use InvalidArgumentException;

abstract class Manager
{
    /**
     * 驱动
     */
    protected array $drivers = [];

    /**
     * 驱动的命名空间
     */
    protected string $namespace = '';

    /**
     * 获取驱动实例
     */
    protected function driver(?string $name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->drivers[$name] = $this->getDriver($name);
    }

    /**
     * 获取驱动实例
     */
    protected function getDriver(string $name)
    {
        return $this->drivers[$name] ?? $this->createDriver($name);
    }

    /**
     * 获取驱动类型
     */
    protected function resolveType(string $name)
    {
        return $name;
    }

    /**
     * 获取驱动配置
     */
    protected function resolveConfig(string $name)
    {
        return $name;
    }

    /**
     * 获取驱动类
     */
    protected function resolveClass(string $type): string
    {
        if ($this->namespace || str_contains($type, '\\')) {
            $class = str_contains($type, '\\') ? $type : $this->namespace.Str::studly($type);

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
    }

    /**
     * 获取驱动参数
     */
    protected function resolveParams($name): array
    {
        $config = $this->resolveConfig($name);

        return [$config];
    }

    /**
     * 创建驱动
     */
    protected function createDriver(string $name)
    {
        $type = $this->resolveType($name);

        $method = 'create'.Str::studly($type).'Driver';

        $params = $this->resolveParams($name);

        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        return new ($this->resolveClass($type));
    }

    /**
     * 移除一个驱动实例
     */
    public function forgetDriver($name = null): static
    {
        $name = $name ?? $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 默认驱动
     */
    abstract public function getDefaultDriver(): string;

    /**
     * 动态调用
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
