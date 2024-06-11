<?php

declare(strict_types=1);

namespace Flame\Queue\Contracts;

interface JobInterface
{
    public function handle();
}
