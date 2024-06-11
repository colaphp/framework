<?php

declare(strict_types=1);

namespace Flame\Queue;

use Flame\Queue\Contracts\Factory;
use Flame\Support\Str;

class QueueManager
{
    private array $config;

    private static array $connections = [];

    public function __construct()
    {
        $this->config = config('queue');
    }

    public function instance(string $queueName = 'default'): Factory
    {
        $queueType = Str::studly($this->config['default']).'Queue';
        $queueConfig = $this->config['connections'][$this->config['default']];

        if (! isset(self::$connections[$queueType])) {
            $queueDriver = __NAMESPACE__.'\\'.$queueType;
            self::$connections[$queueType] = new $queueDriver($queueConfig, $queueName);
        }

        return self::$connections[$queueType];
    }
}
