<?php

namespace Flame\Http\Middleware;

use Flame\Contracts\Middleware;
use Flame\Http\Request;
use Flame\Http\Response;

/**
 * Class HandleCors
 */
class HandleCors implements Middleware
{
    public function process(Request $request, callable $next): Response
    {
        $response = $request->method() == 'OPTIONS' ? response('') : $next($request);

        $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('Origin', '*'),
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
        ]);

        return $response;
    }
}
