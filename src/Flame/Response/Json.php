<?php

declare(strict_types=1);

namespace Flame\Response;

use Exception;
use Flame\Http\Response;
use Flame\Support\Facade\Cookie;
use InvalidArgumentException;
use Throwable;

class Json extends Response
{
    protected array $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];

    protected string $contentType = 'application/json';

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);
        $this->cookie = Cookie::getInstance();
    }

    /**
     * 处理数据
     *
     * @throws Throwable
     */
    protected function output($data): string
    {
        try {
            // 返回JSON数据格式到客户端 包含状态信息
            $data = json_encode($data, $this->options['json_encode_param']);

            if ($data === false) {
                throw new InvalidArgumentException(json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }

            throw $e;
        }
    }
}
