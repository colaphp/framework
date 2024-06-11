<?php

declare(strict_types=1);

namespace Flame\Pusher;

use Flame\Http\Response;
use Flame\Pusher\Exceptions\PusherException;
use Flame\Support\Facade\Request;

class PusherRoute
{
    /**
     * 推送js客户端文件
     */
    public function js(): Response
    {
        return Response::create(__DIR__.'/Scripts/push.js', 'file');
    }

    /**
     * 私有频道鉴权，这里应该使用session辨别当前用户身份，然后确定该用户是否有权限监听channel_name
     *
     * @throws PusherException
     */
    public function auth(): Response
    {
        $config = config('pusher.app');

        $pusher = new PusherApi(
            apiAddress: str_replace('0.0.0.0', '127.0.0.1', $config['api']),
            authKey: $config['app_key'],
            secret: $config['app_secret']
        );

        $channelName = Request::post('channel_name');

        // 这里应该通过session和channel_name判断当前用户是否有权限监听channel_name
        $hasAuthority = true;

        if ($hasAuthority) {
            return Response::create($pusher->socketAuth($channelName, Request::post('socket_id')));
        } else {
            return Response::create('Forbidden', 'html', 403);
        }
    }

    /**
     * 当频道上线以及下线时触发的回调
     * 频道上线：是指某个频道从没有连接在线到有连接在线的事件
     * 频道下线：是指某个频道的所有连接都断开触发的事件
     */
    public function channelHook(): Response
    {
        // 没有x-pusher-signature头视为伪造请求
        if (! $webhookSignature = Request::header('x-pusher-signature')) {
            return Response::create('401 Not authenticated', 'html', 401);
        }

        $body = file_get_contents('php://input');

        // 计算签名，$app_secret 是双方使用的密钥，是保密的，外部无从得知
        $expectedSignature = hash_hmac('sha256', $body, config('pusher.app.app_secret'));

        // 安全校验，如果签名不一致可能是伪造的请求，返回401状态码
        if ($webhookSignature !== $expectedSignature) {
            return Response::create('401 Not authenticated', 'html', 401);
        }

        // 这里存储这上线 下线的channel数据
        $payload = json_decode($body, true);

        $channels_online = $channels_offline = [];

        foreach ($payload['events'] as $event) {
            if ($event['name'] === 'channel_added') {
                $channels_online[] = $event['channel'];
            } elseif ($event['name'] === 'channel_removed') {
                $channels_offline[] = $event['channel'];
            }
        }

        // 业务根据需要处理上下线的channel，例如将在线状态写入数据库，通知其它channel等
        // 上线的所有channel
        echo 'online channels: '.implode(',', $channels_online)."\n";
        // 下线的所有channel
        echo 'offline channels: '.implode(',', $channels_offline)."\n";

        return Response::create('OK');
    }
}
