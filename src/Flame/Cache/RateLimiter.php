<?php

declare(strict_types=1);

namespace Flame\Cache;

use Flame\Support\Facade\Cache;
use Illuminate\Cache\RateLimiter as BaseRateLimiter;

class RateLimiter extends BaseRateLimiter
{
    public function __construct()
    {
        parent::__construct(Cache::store());
    }
}
