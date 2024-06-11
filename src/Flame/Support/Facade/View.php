<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\View\ViewManager;

/**
 * @mixin ViewManager
 */
class View extends Facade
{
    protected static function getFacadeClass(): string
    {
        return ViewManager::class;
    }
}
