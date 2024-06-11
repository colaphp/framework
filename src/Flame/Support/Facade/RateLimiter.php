<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

/**
 * @mixin  \Illuminate\Cache\RateLimiter
 */
class RateLimiter extends Facade
{
    protected static function getFacadeClass(): string
    {
        return \Flame\Cache\RateLimiter::class;
    }
}
