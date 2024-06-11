<?php

declare(strict_types=1);

namespace Flame\Notifications\Channels;

use Exception;
use Flame\Http\HttpClient;
use Flame\Notifications\Exceptions\NotificationException;
use Flame\Notifications\Messages\Message;

class DingRobotChannel
{
    /**
     * 接收机器人消息的用户的userId列表,每次最多传20个。
     */
    const USER_LIST_LENGTH = 20;

    /**
     * @throws Exception
     */
    public function send($notifiable, $notification): void
    {
        /** @var Message $message */
        $message = $notification->toDingRobot($notifiable);
        $config = $message->getConfig();
        $body = $message->getBody();

        $url = 'https://api.dingtalk.com/v1.0/robot/oToMessages/batchSend';
        $opt[CURLOPT_HTTPHEADER] = ['x-acs-dingtalk-access-token:'.$config['access_token']];

        $receivers = $body['userIds'] ?? [];
        $listGroup = array_chunk($receivers, self::USER_LIST_LENGTH);
        foreach ($listGroup as $group) {
            $body['userIds'] = $group;
            $response = HttpClient::postJsonUrl($url, $body, $opt);
            $res = json_decode($response, true);
            if (isset($res['message'])) {
                throw new NotificationException($res['message']);
            }
        }
    }
}
