<?php

namespace Cola\Routing\Middleware;

use Cola\Contracts\Middleware;
use Cola\Http\Request;
use Cola\Http\Response;

/**
 * Class ThrottleRequests
 * @package Cola\Routing\Middleware
 */
class ThrottleRequests implements Middleware
{
    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next): Response
    {
        return $next($request);
    }
}
