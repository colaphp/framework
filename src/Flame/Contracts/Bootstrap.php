<?php

declare(strict_types=1);

namespace Flame\Contracts;

use Workerman\Worker;

interface Bootstrap
{
    /**
     * onWorkerStart
     */
    public static function start(?Worker $worker);
}
