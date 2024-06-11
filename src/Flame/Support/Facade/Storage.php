<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Filesystem\StorageInterface;
use Flame\Filesystem\StorageManager;

/**
 * @mixin StorageInterface
 */
class Storage extends Facade
{
    protected static function getFacadeClass(): string
    {
        return StorageManager::class;
    }
}
