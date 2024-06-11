<?php

declare(strict_types=1);

namespace Flame\Snowflake\Resolver;

use Flame\Snowflake\Contract\SequenceResolver;
use Flame\Support\Facade\Cache;

class RedisSequenceResolver implements SequenceResolver
{
    /**
     * The cache prefix.
     */
    protected string $prefix = '';

    public function sequence(int $currentTime): int
    {
        $key = $this->prefix.$currentTime;

        if (Cache::add($key, 1, 10)) {
            return 0;
        }

        return Cache::increment($key);
    }

    /**
     * Set cache prefix.
     */
    public function setCachePrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }
}
