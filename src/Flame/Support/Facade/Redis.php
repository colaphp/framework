<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Redis\RedisManager;
use Illuminate\Redis\Connections\PhpRedisConnection;

/**
 * @mixin PhpRedisConnection
 */
class Redis extends RedisManager
{

}
