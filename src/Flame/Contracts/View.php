<?php

declare(strict_types=1);

namespace Flame\Contracts;

interface View
{
    public static function assign($name, $value = null);

    public static function render(string $template, array $vars): string;
}
