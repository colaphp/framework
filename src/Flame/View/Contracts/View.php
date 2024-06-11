<?php

declare(strict_types=1);

namespace Flame\View\Contracts;

interface View
{
    public function assign($name, $value = null);

    public function render(string $template, array $vars): string;

    public function contentRender(string $content, array $vars): string;

    public function exists(string $template): bool;
}
