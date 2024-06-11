<?php

declare(strict_types=1);

namespace Flame\Http;

use Exception;

class HttpClient
{
    public string $url;

    /**
     * Default Options
     */
    public array $opts = [
        CURLOPT_TIMEOUT => 15,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ];

    protected array $_maps = [
        'timeout' => CURLOPT_TIMEOUT,
        'ssl' => CURLOPT_SSL_VERIFYPEER,
        'headers' => CURLOPT_HTTPHEADER,
    ];

    public array $info = [];

    public function __construct($url, $opts = [])
    {
        $this->url = $url;
        foreach ($opts as $key => $val) {
            if (isset($this->_maps[$key])) {
                $this->opts[$this->_maps[$key]] = $val;
            } else {
                $this->opts[$key] = $val;
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function getUrl($url, $params = [], $opts = []): string
    {
        $http = new self($url, $opts);

        return $http->get($params);
    }

    /**
     * @throws Exception
     */
    public static function postUrl($url, $data = [], $opts = []): string
    {
        $http = new self($url, $opts);

        return $http->post($data);
    }

    /**
     * @throws Exception
     */
    public static function postJsonUrl($url, $data = [], $opts = []): string
    {
        $http = new self($url, $opts);

        return $http->postJson($data);
    }

    /**
     * @throws Exception
     */
    public static function postPlainUrl($url, string $data, $opts = []): string
    {
        $http = new self($url, $opts);

        return $http->postPlain($data);
    }

    /**
     * HTTP GET
     *
     * @throws Exception
     */
    public function get(array $params = []): string
    {
        $url = $this->url;

        if ($params) {
            $queryStr = http_build_query($params);
            $url .= ((! str_contains($url, '?')) ? "?{$queryStr}" : "&{$queryStr}");
        }

        return $this->request($url, $this->opts);
    }

    /**
     * HTTP POST
     *
     * @throws Exception
     */
    public function post(array $data): string
    {
        $opts = $this->opts;
        $opts[CURLOPT_POST] = true;

        if ($data) {
            $data = http_build_query($data);
        }

        $opts[CURLOPT_POSTFIELDS] = $data;

        return $this->request($this->url, $opts);
    }

    /**
     * HTTP POST JSON
     *
     * @throws Exception
     */
    public function postJson(array $data): string
    {
        $opts = $this->opts;
        $opts[CURLOPT_POST] = true;

        if ($data) {
            $data = json_encode($data);
        }

        $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

        $opts[CURLOPT_POSTFIELDS] = $data;

        return $this->request($this->url, $opts);
    }

    /**
     * HTTP POST text/plain
     *
     * @throws Exception
     */
    public function postPlain(string $data): string
    {
        $opts = $this->opts;
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: text/plain';
        $opts[CURLOPT_POSTFIELDS] = $data;

        return $this->request($this->url, $opts);
    }

    /**
     * HTTP request
     *
     * @throws Exception
     */
    public function request(string $url, array $opts): string
    {
        if (! function_exists('curl_init')) {
            throw new Exception('Can not find curl extension');
        }

        $curl = curl_init();
        $opts[CURLOPT_URL] = $url;
        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);

        $this->info = curl_getinfo($curl) + ['errno' => $errno, 'error' => $error];

        if ($errno !== 0) {
            throw new Exception($error, $errno);
        }

        curl_close($curl);

        return $response;
    }
}
