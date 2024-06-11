<?php

declare(strict_types=1);

namespace Flame\Contracts\Support;

interface Jsonable
{
    public function toJson(int $options = 0): string;
}
