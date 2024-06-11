<?php

declare(strict_types=1);

namespace Flame\JsonRpc;

interface Procedure
{
    public function handle(array $request): array;
}
