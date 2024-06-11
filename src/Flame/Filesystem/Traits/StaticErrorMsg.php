<?php

namespace Flame\Filesystem\Traits;

trait StaticErrorMsg
{
    public static string $message = 'success';

    public static function setStaticError(bool $success, string $message): bool
    {
        self::$message = $message;

        return $success;
    }

    public static function getStaticMessage(): string
    {
        return self::$message;
    }
}
