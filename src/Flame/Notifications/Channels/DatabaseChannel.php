<?php

declare(strict_types=1);

namespace Flame\Notifications\Channels;

use app\service\NotificationService;
use Flame\Notifications\Notification;
use RuntimeException;

class DatabaseChannel
{
    /**
     * 发送给定的通知
     */
    public function send($notifiable, Notification $notification)
    {
        $data = $this->buildPayload($notifiable, $notification);
        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        $notificationService = new NotificationService();

        return $notificationService->save($data);
    }

    /**
     * 为 Database Notification Model 构建一个数组有效负载
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        return [
            'uuid' => $notification->id,
            'type' => get_class($notification),
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getId(),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
        ];
    }

    /**
     * 获取通知的数据
     *
     * @throws RuntimeException
     */
    protected function getData($notifiable, Notification $notification): array
    {
        if (method_exists($notification, 'toDatabase')) {
            return is_array($data = $notification->toDatabase($notifiable))
                ? $data : $data->data;
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        throw new RuntimeException('Notification is missing toDatabase / toArray method.');
    }
}
