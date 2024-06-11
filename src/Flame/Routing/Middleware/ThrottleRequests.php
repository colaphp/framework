<?php

namespace Flame\Routing\Middleware;

use Flame\Contracts\Middleware;
use Flame\Http\Request;
use Flame\Http\Response;

/**
 * Class ThrottleRequests
 */
class ThrottleRequests implements Middleware
{
    public function process(Request $request, callable $next): Response
    {
        return $next($request);
    }
}
