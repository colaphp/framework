<?php

declare(strict_types=1);

namespace Flame\Routing;

use Flame\Foundation\Contract\EnumMethodInterface;
use Flame\Http\Response;
use Throwable;

abstract class Controller
{
    /**
     * 返回成功JSON数据
     */
    protected function success($data = null): Response
    {
        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * 返回失败JSON数据
     */
    protected function fail(EnumMethodInterface|Throwable|string $message = 'fail', $code = 500): Response
    {
        if ($message instanceof EnumMethodInterface) {
            $code = $message->getValue();
            $message = $message->getDescription();
        } else if ($message instanceof Throwable) {
            $code = $message->getCode();
            $message = $message->getMessage();
        }

        return json([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ]);
    }
}
