<?php

namespace Cola\Contracts;

use Workerman\Worker;

/**
 * Interface Bootstrap
 * @package Cola\Contracts
 */
interface Bootstrap
{
    /**
     * onWorkerStart
     *
     * @param Worker $worker
     * @return mixed
     */
    public static function start(Worker $worker);
}
