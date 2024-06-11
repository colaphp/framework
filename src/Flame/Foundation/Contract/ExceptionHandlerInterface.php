<?php

declare(strict_types=1);

namespace Flame\Foundation\Contract;

use Flame\Http\Request;
use Flame\Http\Response;
use Throwable;

interface ExceptionHandlerInterface
{
    public function report(Throwable $e);

    public function render(Request $request, Throwable $e): Response;
}
