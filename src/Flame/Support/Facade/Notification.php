<?php

declare(strict_types=1);

namespace Flame\Support\Facade;

use Flame\Notifications\ChannelManager;

/**
 * @mixin ChannelManager
 */
class Notification extends Facade
{
    protected static function getFacadeClass(): string
    {
        return ChannelManager::class;
    }
}
