<?php

declare(strict_types=1);

namespace Flame\Foundation\Configuration;

use Flame\Foundation\Exception\BusinessException;
use Flame\Foundation\Exception\ExceptionHandler;
use Flame\Http\Request;
use Flame\Http\Response;
use Throwable;

class Exceptions extends ExceptionHandler
{
    public array $dontReport = [
        BusinessException::class,
    ];

    public function render(Request $request, Throwable $e): Response
    {
        if (($e instanceof BusinessException) && ($response = $e->render($request))) {
            return $response;
        }

        return parent::render($request, $e);
    }
}
