<?php

declare(strict_types=1);

namespace Flame\Console\Contracts;

interface ScheduleTaskInterface
{
    public function cron(): ?string;

    public function handle();
}
