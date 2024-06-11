<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Queue\Contracts\Factory;
use Flame\Queue\QueueManager;

/**
 * @method static Factory instance(string $queueName)
 */
class Queue extends Facade
{
    protected static function getFacadeClass(): string
    {
        return QueueManager::class;
    }
}
