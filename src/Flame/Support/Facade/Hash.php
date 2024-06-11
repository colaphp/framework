<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Hashing\HashManager;

/**
 * @mixin HashManager
 */
class Hash extends Facade
{
    protected static function getFacadeClass(): string
    {
        return HashManager::class;
    }
}
