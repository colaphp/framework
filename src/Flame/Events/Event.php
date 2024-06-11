<?php

declare(strict_types=1);

namespace Flame\Events;

use Flame\Container\Container;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Event
{
    /**
     * 监听者
     */
    protected array $listener = [];

    /**
     * 事件别名
     */
    protected array $bind = [];

    /**
     * 应用对象
     */
    protected Container $app;

    public function __construct()
    {
        $this->app = new Container();
    }

    /**
     * 批量注册事件监听
     */
    public function listenEvents(array $events): static
    {
        foreach ($events as $event => $listeners) {
            if (isset($this->bind[$event])) {
                $event = $this->bind[$event];
            }

            $this->listener[$event] = array_merge($this->listener[$event] ?? [], $listeners);
        }

        return $this;
    }

    /**
     * 注册事件监听
     */
    public function listen(string $event, $listener, bool $first = false): static
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        if ($first && isset($this->listener[$event])) {
            array_unshift($this->listener[$event], $listener);
        } else {
            $this->listener[$event][] = $listener;
        }

        return $this;
    }

    /**
     * 是否存在事件监听
     */
    public function hasListener(string $event): bool
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        return isset($this->listener[$event]);
    }

    /**
     * 移除事件监听
     */
    public function remove(string $event): void
    {
        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        unset($this->listener[$event]);
    }

    /**
     * 指定事件别名标识 便于调用
     */
    public function bind(array $events): static
    {
        $this->bind = array_merge($this->bind, $events);

        return $this;
    }

    /**
     * 注册事件订阅者
     *
     * @throws ReflectionException
     */
    public function subscribe($subscriber): static
    {
        $subscribers = (array) $subscriber;

        foreach ($subscribers as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = $this->app->make($subscriber);
            }

            if (method_exists($subscriber, 'subscribe')) {
                // 手动订阅
                $subscriber->subscribe($this);
            } else {
                // 智能订阅
                $this->observe($subscriber);
            }
        }

        return $this;
    }

    /**
     * 自动注册事件观察者
     *
     * @throws ReflectionException
     */
    public function observe($observer, string $prefix = ''): static
    {
        if (is_string($observer)) {
            $observer = $this->app->make($observer);
        }

        $reflect = new ReflectionClass($observer);
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);

        if (empty($prefix) && $reflect->hasProperty('eventPrefix')) {
            $reflectProperty = $reflect->getProperty('eventPrefix');
            $prefix = $reflectProperty->getValue($observer);
        }

        foreach ($methods as $method) {
            $name = $method->getName();
            if (str_starts_with($name, 'on')) {
                $this->listen($prefix.substr($name, 2), [$observer, $name]);
            }
        }

        return $this;
    }

    /**
     * 触发事件
     */
    public function trigger($event, $params = null, bool $once = false)
    {
        if (is_object($event)) {
            $params = $event;
            $event = $event::class;
        }

        if (isset($this->bind[$event])) {
            $event = $this->bind[$event];
        }

        $result = [];
        $listeners = $this->listener[$event] ?? [];

        if (str_contains($event, '.')) {
            [$prefix, $event] = explode('.', $event, 2);
            if (isset($this->listener[$prefix.'.*'])) {
                $listeners = array_merge($listeners, $this->listener[$prefix.'.*']);
            }
        }

        $listeners = array_unique($listeners, SORT_REGULAR);

        foreach ($listeners as $key => $listener) {
            $result[$key] = $this->dispatch($listener, $params);

            if ($result[$key] === false || (! is_null($result[$key]) && $once)) {
                break;
            }
        }

        return $once ? end($result) : $result;
    }

    /**
     * 触发事件(只获取一个有效返回值)
     */
    public function until($event, $params = null)
    {
        return $this->trigger($event, $params, true);
    }

    /**
     * 执行事件调度
     *
     * @throws ReflectionException
     */
    protected function dispatch($event, $params = null)
    {
        if (! is_string($event)) {
            $call = $event;
        } elseif (str_contains($event, '::')) {
            $call = $event;
        } else {
            $obj = $this->app->make($event);
            $call = [$obj, 'handle'];
        }

        return $this->app->invoke($call, [$params]);
    }
}
