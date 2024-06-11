<?php

declare(strict_types=1);

namespace Flame\Notifications\Channels;

use Exception;
use Flame\Http\HttpClient;
use Flame\Notifications\Exceptions\NotificationException;
use Flame\Notifications\Messages\Message;
use Flame\Support\Facade\Log;

class DingTalkChannel
{
    /**
     * @throws Exception
     */
    public function send($notifiable, $notification): void
    {
        /** @var Message $message */
        $message = $notification->toDingTalk($notifiable);
        $config = $message->getConfig();

        $url = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token='.$config['access_token'];
        $response = HttpClient::postJsonUrl($url, $message->getBody());

        Log::info(__CLASS__.':'.$response);

        $res = json_decode($response, true);
        if ($res['errcode'] !== 0) {
            throw new NotificationException($res['errmsg']);
        }
    }
}
