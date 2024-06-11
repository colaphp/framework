<?php

declare(strict_types=1);

namespace Flame\Notifications;

use Flame\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * 通知的唯一标识符
     */
    public ?string $id = null;
}
