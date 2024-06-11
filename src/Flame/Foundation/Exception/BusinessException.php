<?php

declare(strict_types=1);

namespace Flame\Foundation\Exception;

use Exception;
use Flame\Http\Request;
use Flame\Http\Response;

class BusinessException extends Exception
{
    public function render(Request $request): ?Response
    {
        if ($request->expectsJson()) {
            $code = $this->getCode();
            $json = ['code' => $code ?: 500, 'msg' => $this->getMessage(), 'data' => null];

            return new Response(200, ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return new Response(200, [], $this->getMessage());
    }
}
