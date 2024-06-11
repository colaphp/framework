<?php

declare(strict_types=1);

namespace Flame\Cookie;

use DateTimeInterface;
use Flame\Support\Facade\Request;

class CookieManager
{
    /**
     * 配置参数
     */
    protected array $config = [
        // cookie 保存时间
        'expire' => 0,
        // cookie 保存路径
        'path' => '/',
        // cookie 有效域名
        'domain' => '',
        // cookie 启用安全传输
        'secure' => false,
        // httponly设置
        'httponly' => false,
        // samesite 设置，支持 'strict' 'lax'
        'samesite' => '',
    ];

    /**
     * Cookie写入数据
     */
    protected array $cookie = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->config = config('cookie');
    }

    /**
     * 获取cookie
     */
    public function get(string $name = '', $default = null): ?string
    {
        return Request::cookie($name, $default);
    }

    /**
     * 是否存在Cookie参数
     */
    public function has(string $name): bool
    {
        return ! is_null($this->get($name));
    }

    /**
     * Cookie 设置
     */
    public function set(string $name, string $value, $option = null): void
    {
        // 参数设置(会覆盖黙认设置)
        if (! is_null($option)) {
            if (is_numeric($option) || $option instanceof DateTimeInterface) {
                $option = ['expire' => $option];
            }

            $config = array_merge($this->config, array_change_key_case($option));
        } else {
            $config = $this->config;
        }

        if ($config['expire'] instanceof DateTimeInterface) {
            $expire = $config['expire']->getTimestamp();
        } else {
            $expire = ! empty($config['expire']) ? time() + intval($config['expire']) : 0;
        }

        $this->setCookie($name, $value, $expire, $config);
    }

    /**
     * Cookie 保存
     */
    protected function setCookie(string $name, string $value, int $expire, array $option = []): void
    {
        $this->cookie[$name] = [$value, $expire, $option];
    }

    /**
     * 永久保存Cookie数据
     */
    public function forever(string $name, string $value = '', $option = null): void
    {
        if (is_null($option) || is_numeric($option)) {
            $option = [];
        }

        $option['expire'] = 315360000;

        $this->set($name, $value, $option);
    }

    /**
     * Cookie删除
     */
    public function delete(string $name, array $options = []): void
    {
        $config = array_merge($this->config, array_change_key_case($options));
        $this->setCookie($name, '', time() - 3600, $config);
    }

    /**
     * 获取cookie保存数据
     */
    public function getCookie(): array
    {
        return $this->cookie;
    }

    /**
     * 保存Cookie
     */
    public function save(): void
    {
        foreach ($this->cookie as $name => $val) {
            [$value, $expire, $option] = $val;

            $this->saveCookie(
                $name,
                $value,
                $expire,
                $option['path'],
                $option['domain'],
                (bool) $option['secure'],
                (bool) $option['httponly'],
                $option['samesite']
            );
        }
    }

    /**
     * 保存Cookie
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite): void
    {
        setcookie($name, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);
    }
}
