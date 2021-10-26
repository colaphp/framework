<?php

namespace Swift\Log;

use Monolog\Logger;

/**
 * Class Log
 * @package Swift\Log
 *
 * @method static void log($level, $message, array $context = [])
 * @method static void debug($message, array $context = [])
 * @method static void info($message, array $context = [])
 * @method static void notice($message, array $context = [])
 * @method static void warning($message, array $context = [])
 * @method static void error($message, array $context = [])
 * @method static void critical($message, array $context = [])
 * @method static void alert($message, array $context = [])
 * @method static void emergency($message, array $context = [])
 */
class Log
{
    /**
     * @var array
     */
    protected static $_instance = [];

    /**
     * @param string $name
     * @return Logger
     */
    public static function channel($name = 'default')
    {
        if (!static::$_instance) {
            $configs = config('log', []);
            foreach ($configs as $channel => $config) {
                $logger = static::$_instance[$channel] = new Logger($channel);
                foreach ($config['handlers'] as $handler_config) {
                    $handler = new $handler_config['class'](... \array_values($handler_config['constructor']));
                    if (isset($handler_config['formatter'])) {
                        $formatter = new $handler_config['formatter']['class'](... \array_values($handler_config['formatter']['constructor']));
                        $handler->setFormatter($formatter);
                    }
                    $logger->pushHandler($handler);
                }
            }
        }

        return static::$_instance[$name];
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::channel('default')->{$name}(... $arguments);
    }
}
