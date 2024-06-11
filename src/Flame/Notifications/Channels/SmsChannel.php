<?php

declare(strict_types=1);

namespace Flame\Notifications\Channels;

use Exception;
use Flame\Notifications\Messages\Message;
use Flame\Sms\SmsManager;

class SmsChannel
{
    protected SmsManager $smsSender;

    public function __construct()
    {
        $this->smsSender = new SmsManager();
    }

    /**
     * @throws Exception
     */
    public function send($notifiable, $notification): void
    {
        /** @var Message $message */
        $message = $notification->toSms($notifiable);

        $this->smsSender->send(
            mobile: $message->getTo(), // 接收短信的手机号
            template: $message->getTitle(), // 短信模板
            data: $message->getBody() // 短信内容
        );
    }
}
