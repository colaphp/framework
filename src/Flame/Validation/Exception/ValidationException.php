<?php

declare(strict_types=1);

namespace Flame\Validation\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * 表单验证异常
 */
class ValidationException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
