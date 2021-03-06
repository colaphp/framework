<?php

namespace Cola\Contracts;

use Cola\Http\Request;
use Cola\Http\Response;

/**
 * Interface Middleware
 * @package Cola\Contracts
 */
interface Middleware
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $handler): Response;
}
