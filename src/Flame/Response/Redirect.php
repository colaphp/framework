<?php

declare(strict_types=1);

namespace Flame\Response;

use Flame\Http\Response;
use Flame\Session\SessionManager;
use Flame\Support\Facade\Cookie;
use Flame\Support\Facade\Request;
use Flame\Support\Facade\Session;

class Redirect extends Response
{
    protected SessionManager $session;

    public function __construct($data = '', int $code = 302)
    {
        $this->init((string) $data, $code);

        $this->cookie = Cookie::getInstance();
        $this->session = Session::getInstance();

        $this->cacheControl('no-cache,must-revalidate');
    }

    public function data($data): static
    {
        $this->header['Location'] = $data;

        return parent::data($data);
    }

    /**
     * 处理数据
     */
    protected function output($data): string
    {
        return '';
    }

    /**
     * 重定向传值（通过Session）
     */
    public function with($name, $value = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->session->flash($key, $val);
            }
        } else {
            $this->session->flash($name, $value);
        }

        return $this;
    }

    /**
     * 记住当前url后跳转
     */
    public function remember(): static
    {
        $this->session->set('redirect_url', Request::fullUrl());

        return $this;
    }

    /**
     * 跳转到上次记住的url
     */
    public function restore(): static
    {
        if ($this->session->has('redirect_url')) {
            $this->data = $this->session->get('redirect_url');
            $this->session->delete('redirect_url');
        }

        return $this;
    }
}
