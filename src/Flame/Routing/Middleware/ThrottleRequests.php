<?php

declare(strict_types=1);

namespace Flame\Routing\Middleware;

use Flame\Contracts\Middleware;
use Flame\Http\Request;
use Flame\Http\Response;

class ThrottleRequests implements Middleware
{
    public function process(Request $request, callable $next): Response
    {
        return $next($request);
    }
}
