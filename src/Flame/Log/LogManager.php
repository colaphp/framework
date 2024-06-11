<?php

declare(strict_types=1);

namespace Flame\Log;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * @mixin Logger
 */
class LogManager
{
    protected static array $instance = [];

    public static function channel(string $name = 'default'): Logger
    {
        if (! isset(static::$instance[$name])) {
            $config = config('log', [])[$name];
            $handlers = self::handlers($config);
            $processors = self::processors($config);
            static::$instance[$name] = new Logger($name, $handlers, $processors);
        }

        return static::$instance[$name];
    }

    protected static function handlers(array $config): array
    {
        $handlerConfigs = $config['handlers'] ?? [[]];
        $handlers = [];
        foreach ($handlerConfigs as $value) {
            $class = $value['class'] ?? [];
            $constructor = $value['constructor'] ?? [];

            $formatterConfig = $value['formatter'] ?? [];

            $class && $handlers[] = self::handler($class, $constructor, $formatterConfig);
        }

        return $handlers;
    }

    protected static function handler(string $class, array $constructor, array $formatterConfig): HandlerInterface
    {
        /** @var HandlerInterface $handler */
        $handler = new $class(...array_values($constructor));

        if ($handler instanceof FormattableHandlerInterface && $formatterConfig) {
            $formatterClass = $formatterConfig['class'];
            $formatterConstructor = $formatterConfig['constructor'];

            /** @var FormatterInterface $formatter */
            $formatter = new $formatterClass(...array_values($formatterConstructor));

            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    protected static function processors(array $config): array
    {
        $result = [];
        if (! isset($config['processors']) && isset($config['processor'])) {
            $config['processors'] = [$config['processor']];
        }

        foreach ($config['processors'] ?? [] as $value) {
            if (is_array($value) && isset($value['class'])) {
                $value = new $value['class'](...array_values($value['constructor'] ?? []));
            }
            $result[] = $value;
        }

        return $result;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return static::channel()->{$name}(...$arguments);
    }
}
