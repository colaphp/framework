<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Http\Request as RequestFactory;

/**
 * @mixin  RequestFactory
 */
class Request extends Facade
{
    protected static function getFacadeClass(): string
    {
        return RequestFactory::class;
    }
}
