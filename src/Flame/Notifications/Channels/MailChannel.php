<?php

declare(strict_types=1);

namespace Flame\Notifications\Channels;

use Flame\Mail\MailManager;
use Flame\Notifications\Messages\Message;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailChannel
{
    /**
     * 邮件发送器
     */
    protected MailManager $mailer;

    /**
     * 创建一个新的邮件通道实例
     */
    public function __construct()
    {
        $this->mailer = new MailManager();
    }

    /**
     * 发送给定的通知
     *
     * @throws TransportExceptionInterface
     */
    public function send($notifiable, $notification): void
    {
        /** @var Message $message */
        $message = $notification->toMail($notifiable);

        $this->mailer->send($message->getTo(), $message->getTitle(), $message->getBody());
    }
}
