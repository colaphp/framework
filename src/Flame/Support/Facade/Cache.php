<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Cache\CacheFactory;
use Illuminate\Cache\Repository;

/**
 * @mixin Repository
 */
class Cache extends Facade
{
    protected static function getFacadeClass(): string
    {
        return CacheFactory::class;
    }
}
