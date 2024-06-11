<?php

declare(strict_types=1);

namespace Flame\Snowflake\Contract;

interface SequenceResolver
{
    public function sequence(int $currentTime): int;
}
