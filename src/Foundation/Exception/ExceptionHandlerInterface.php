<?php

namespace Cola\Foundation\Exception;

use Cola\Http\Request;
use Cola\Http\Response;
use Throwable;

/**
 * Interface ExceptionHandlerInterface
 * @package Cola\Foundation\Exception
 */
interface ExceptionHandlerInterface
{
    /**
     * @param Throwable $e
     * @return mixed
     */
    public function report(Throwable $e);

    /**
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render(Request $request, Throwable $e): Response;
}