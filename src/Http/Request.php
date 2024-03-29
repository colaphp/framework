<?php

namespace Cola\Http;

use Cola\Foundation\App;
use Cola\Support\Arr;
use Cola\Support\Str;
use Workerman\Protocols\Http\Request as WorkerRequest;

/**
 * Class Request
 * @package Cola\Http
 */
class Request extends WorkerRequest
{
    /**
     * @var string
     */
    public $app = null;

    /**
     * @var string
     */
    public $controller = null;

    /**
     * @var string
     */
    public $action = null;

    /**
     * @var Route
     */
    public $route = null;

    /**
     * @return mixed|null
     */
    public function all()
    {
        return $this->post() + $this->get();
    }

    /**
     * @param string $name
     * @param string|null $default
     * @return mixed|null
     */
    public function input($name, $default = null)
    {
        $post = $this->post();
        if (isset($post[$name])) {
            return $post[$name];
        }
        $get = $this->get();
        return isset($get[$name]) ? $get[$name] : $default;
    }

    /**
     * Determine if the request contains a given input item key.
     *
     * @param string|array $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->has($key);
    }

    /**
     * Determine if the request contains a given input item key.
     *
     * @param string|array $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (!Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $keys
     * @return array
     */
    public function only(array $keys)
    {
        $all = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /**
     * @param array $keys
     * @return mixed|null
     */
    public function except(array $keys)
    {
        $all = $this->all();
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        return $all;
    }

    /**
     * @param string|null $name
     * @return null|array|UploadFile
     */
    public function file($name = null)
    {
        $files = parent::file($name);
        if (null === $files) {
            return $name === null ? [] : null;
        }
        if ($name !== null) {
            // Multi files
            if (is_array(current($files))) {
                return $this->parseFiles($files);
            }
            return $this->parseFile($files);
        }
        $upload_files = [];
        foreach ($files as $name => $file) {
            // Multi files
            if (is_array(current($file))) {
                $upload_files[$name] = $this->parseFiles($file);
            } else {
                $upload_files[$name] = $this->parseFile($file);
            }
        }
        return $upload_files;
    }

    /**
     * @param $file
     * @return UploadFile
     */
    protected function parseFile($file)
    {
        return new UploadFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
    }

    /**
     * @param array $files
     * @return array
     */
    protected function parseFiles($files)
    {
        $upload_files = [];
        foreach ($files as $key => $file) {
            if (is_array(current($file))) {
                $upload_files[$key] = $this->parseFiles($file);
            } else {
                $upload_files[$key] = $this->parseFile($file);
            }
        }
        return $upload_files;
    }

    /**
     * @return string
     */
    public function getRemoteIp()
    {
        return App::connection()->getRemoteIp();
    }

    /**
     * @return int
     */
    public function getRemotePort()
    {
        return App::connection()->getRemotePort();
    }

    /**
     * @return string
     */
    public function getLocalIp()
    {
        return App::connection()->getLocalIp();
    }

    /**
     * @return int
     */
    public function getLocalPort()
    {
        return App::connection()->getLocalPort();
    }

    /**
     * @param bool $safe_mode
     * @return string
     */
    public function getRealIp($safe_mode = true)
    {
        $remote_ip = $this->getRemoteIp();
        if ($safe_mode && !static::isIntranetIp($remote_ip)) {
            return $remote_ip;
        }
        $via = $this->header('via', $remote_ip);
        $client_ip = $this->header('x-client-ip', $via);
        $real_ip = $this->header('x-real-ip', $client_ip);
        $forwarded = $this->header('x-forwarded-for', $real_ip);
        return $this->header('client-ip', $forwarded);
    }

    /**
     * @return string
     */
    public function url()
    {
        return '//' . $this->host() . $this->path();
    }

    /**
     * @return string
     */
    public function fullUrl()
    {
        return '//' . $this->host() . $this->uri();
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public function isPjax()
    {
        return (bool)$this->header('X-PJAX');
    }

    /**
     * @return bool
     */
    public function expectsJson()
    {
        return ($this->isAjax() && !$this->isPjax()) || $this->acceptJson();
    }

    /**
     * @return bool
     */
    public function acceptJson()
    {
        return false !== strpos($this->header('accept'), 'json');
    }

    /**
     * @param string $ip
     * @return bool
     */
    public static function isIntranetIp($ip)
    {
        $reserved_ips = [
            '167772160' => 184549375,  /*    10.0.0.0 -  10.255.255.255 */
            '3232235520' => 3232301055, /* 192.168.0.0 - 192.168.255.255 */
            '2130706432' => 2147483647, /*   127.0.0.0 - 127.255.255.255 */
            '2886729728' => 2887778303, /*  172.16.0.0 -  172.31.255.255 */
        ];

        $ip_long = ip2long($ip);

        foreach ($reserved_ips as $ip_start => $ip_end) {
            if (($ip_long >= $ip_start) && ($ip_long <= $ip_end)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 生成请求令牌
     * @access public
     * @param  string $name 令牌名称
     * @param  mixed  $type 令牌生成方法
     * @return string
     */
    public function build_token(string $name = '__token__', $type = 'md5'): string
    {
        $type  = is_callable($type) ? $type : 'md5';
        $token = call_user_func($type, Str::random() . microtime(true));

        $this->session()->put($name, $token);

        return $token;
    }

    /**
     * 检查请求令牌
     * @access public
     * @param  string $token 令牌名称
     * @param  array  $data  表单数据
     * @return bool
     */
    public function check_token(string $token = '__token__', array $data = []): bool
    {
        if (in_array($this->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if (!$this->session->has($token)) {
            // 令牌数据无效
            return false;
        }

        // Header验证
        if ($this->header('X-CSRF-TOKEN') && $this->session->get($token) === $this->header('X-CSRF-TOKEN')) {
            // 防止重复提交
            $this->session->delete($token); // 验证完成销毁session
            return true;
        }

        if (empty($data)) {
            $data = $this->post();
        }

        // 令牌验证
        if (isset($data[$token]) && $this->session->get($token) === $data[$token]) {
            // 防止重复提交
            $this->session->delete($token); // 验证完成销毁session
            return true;
        }

        // 开启TOKEN重置
        $this->session->delete($token);
        return false;
    }
}
