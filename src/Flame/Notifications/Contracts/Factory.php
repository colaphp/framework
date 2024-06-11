<?php

declare(strict_types=1);

namespace Flame\Notifications\Contracts;

interface Factory
{
    /**
     * 按名称获取通道实例
     */
    public function channel($name = null);

    /**
     * 将给定的通知发送到给定的可通知实体
     */
    public function send($notifiables, $notification);

    /**
     * 立即发送给定的通知
     */
    public function sendNow($notifiables, $notification);
}
