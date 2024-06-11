<?php

declare(strict_types=1);

namespace Flame\Foundation\Attribute;

use Attribute;

#[Attribute]
class ScheduledAttribute
{
    private string $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
