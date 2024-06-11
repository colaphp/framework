<?php

declare(strict_types=1);

namespace Flame\Notifications;

use Flame\Notifications\Channels\DatabaseChannel;
use Flame\Notifications\Channels\DingRobotChannel;
use Flame\Notifications\Channels\DingTalkChannel;
use Flame\Notifications\Channels\MailChannel;
use Flame\Notifications\Channels\SmsChannel;
use Flame\Notifications\Channels\WechatChannel;
use Flame\Notifications\Contracts\Factory as FactoryContract;
use Flame\Support\Manager;
use InvalidArgumentException;

class ChannelManager extends Manager implements FactoryContract
{
    /**
     * 用于传递消息的默认通道
     */
    protected string $defaultChannel = 'mail';

    /**
     * 将给定的通知发送到给定的可通知实体
     */
    public function send($notifiables, $notification): void
    {
        (new NotificationSender($this))->send($notifiables, $notification);
    }

    /**
     * 立即发送给定的通知
     */
    public function sendNow($notifiables, $notification, ?array $channels = null): void
    {
        (new NotificationSender($this))->sendNow($notifiables, $notification, $channels);
    }

    /**
     * 获取通道实例
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * 创建数据库驱动程序的实例
     */
    protected function createDatabaseDriver(): DatabaseChannel
    {
        return new DatabaseChannel();
    }

    /**
     * 创建钉钉驱动程序的实例
     */
    protected function createDingTalkDriver(): DingTalkChannel
    {
        return new DingTalkChannel();
    }

    /**
     * 创建钉钉机器人程序的实例
     */
    protected function createDingRobotDriver(): DingRobotChannel
    {
        return new DingRobotChannel();
    }

    /**
     * 创建邮件驱动程序的实例
     */
    protected function createMailDriver(): MailChannel
    {
        return new MailChannel();
    }

    /**
     * 创建短信驱动程序的实例
     */
    protected function createSmsDriver(): SmsChannel
    {
        return new SmsChannel();
    }

    /**
     * 创建微信驱动程序的实例
     */
    protected function createWechatDriver(): WechatChannel
    {
        return new WechatChannel();
    }

    /**
     * 创建一个新的驱动程序实例
     *
     * @throws InvalidArgumentException
     */
    protected function createDriver(string $name)
    {
        try {
            return parent::createDriver($name);
        } catch (InvalidArgumentException $e) {
            if (class_exists($name)) {
                return new $name;
            }

            throw $e;
        }
    }

    /**
     * 获取默认通道驱动程序名称
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultChannel;
    }

    /**
     * 获取默认通道驱动程序名称
     */
    public function deliversVia(): string
    {
        return $this->getDefaultDriver();
    }

    /**
     * 设置默认通道驱动程序名称
     */
    public function deliverVia(string $channel): void
    {
        $this->defaultChannel = $channel;
    }
}
