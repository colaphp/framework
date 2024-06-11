<?php

declare(strict_types=1);

namespace Flame\Auth\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * 用户认证异常
 */
class AuthenticationException extends Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?? 'Unauthorized', Response::HTTP_UNAUTHORIZED);
    }
}
