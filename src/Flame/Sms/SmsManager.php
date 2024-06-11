<?php

declare(strict_types=1);

namespace Flame\Sms;

use Exception;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Throwable;

class SmsManager
{
    /**
     * 短信配置
     */
    private array $config;

    public function __construct()
    {
        $this->config = config('sms');
    }

    /**
     * @throws Exception
     */
    public function send(string $mobile, string $template, array $data): array
    {
        $templates = $this->config['templates'];
        if (! isset($templates[$template])) {
            throw new Exception('没有找到短信模板');
        }

        try {
            $easySms = new EasySms($this->config);
            $smsKey = array_key_first($templates[$template]);

            return $easySms->send($mobile, [
                'template' => $smsKey,
                'data' => $data,
            ]);
        } catch (NoGatewayAvailableException $e) {
            throw new Exception(var_export($e->getExceptions(), true));
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 短信内容模板解析
     */
    private function contentParser(string $content, array $data): string
    {
        // 替换消息变量
        preg_match_all('/\$\{(.+?)\\\}/', $content, $matches);
        foreach ($matches[1] as $vo) {
            $content = str_replace('${'.$vo.'}', $data[$vo], $content);
        }

        return $content;
    }
}
