<?php

declare(strict_types=1);

namespace Flame\Contracts;

use Flame\Http\Request;
use Flame\Http\Response;

/**
 * Interface Middleware
 */
interface Middleware
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(Request $request, callable $handler): Response;
}
