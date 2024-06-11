<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Sms\SmsManager;

/**
 * @mixin SmsManager
 */
class Sms extends Facade
{
    protected static function getFacadeClass(): string
    {
        return SmsManager::class;
    }
}
