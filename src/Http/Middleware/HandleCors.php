<?php

namespace Cola\Http\Middleware;

use Cola\Contracts\Middleware;
use Cola\Http\Request;
use Cola\Http\Response;

/**
 * Class HandleCors
 * @package Cola\Http\Middleware
 */
class HandleCors implements Middleware
{
    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
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
