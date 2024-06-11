<?php

declare(strict_types=1);

namespace Flame\Routing;

use Flame\Foundation\Contract\EnumMethodInterface;
use Flame\Http\Response;
use Throwable;

/**
 * 公共控制器
 */
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
    protected function fail(Throwable|EnumMethodInterface|string $message = 'fail', $code = 500): Response
    {
        if ($message instanceof Throwable) {
            $code = $message->getCode();
            $message = $message->getMessage();
        } elseif ($message instanceof EnumMethodInterface) {
            $code = $message->getValue();
            $message = $message->getDescription();
        }

        return json([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ]);
    }
}
