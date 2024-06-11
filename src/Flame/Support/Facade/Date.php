<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Support\DateFactory;

/**
 * @mixin DateFactory
 */
class Date extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return DateFactory::class;
    }
}
