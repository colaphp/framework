<?php

declare(strict_types=1);

namespace Flame\Notifications;

use Flame\Queue\Contracts\ShouldQueue;
use Flame\Support\Str;

class NotificationSender
{
    /**
     * 通知管理器实例
     */
    protected ChannelManager $manager;

    /**
     * 创建一个新的通知发送方实例
     */
    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    /**
     * 将给定的通知发送到给定的可通知实体
     */
    public function send($notifiables, $notification): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueue) {
            $this->queueNotification($notifiables, $notification);

            return;
        }

        $this->sendNow($notifiables, $notification);
    }

    /**
     * 立即发送给定的通知
     */
    public function sendNow($notifiables, $notification, ?array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (empty($viaChannels = $channels ?: $notification->via($notifiable))) {
                continue;
            }

            $notificationId = Str::uuid()->toString();

            foreach ((array) $viaChannels as $channel) {
                $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
            }
        }
    }

    /**
     * 通过通道将给定的通知发送给给定的通知对象
     */
    protected function sendToNotifiable($notifiable, $id, $notification, $channel): void
    {
        if (! $notification->id) {
            $notification->id = $id;
        }

        if (! $this->shouldSendNotification($notifiable, $notification, $channel)) {
            return;
        }

        $this->manager->channel($channel)->send($notifiable, $notification);
    }

    /**
     * 确定是否可以发送通知
     */
    protected function shouldSendNotification($notifiable, $notification, $channel): bool
    {
        if (method_exists($notification, 'shouldSend') &&
            $notification->shouldSend($notifiable, $channel) === false) {
            return false;
        }

        return true;
    }

    /**
     * 将给定的通知实例排队.
     */
    protected function queueNotification($notifiables, $notification): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            $notificationId = Str::uuid()->toString();

            foreach ((array) $original->via($notifiable) as $channel) {
                $notification = clone $original;

                if (! $notification->id) {
                    $notification->id = $notificationId;
                }

                // TODO push queue
            }
        }
    }

    /**
     * 如有必要，将可通知对象格式化为数组
     */
    protected function formatNotifiables($notifiables): array
    {
        return is_array($notifiables) ? $notifiables : [$notifiables];
    }
}
