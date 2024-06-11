<?php

declare(strict_types=1);

namespace Flame\JsonRpc;

use Exception;
use Flame\Support\Facade\Crypt;

class JsonRpcClient
{
    private string $url;

    private array $headers = ['Content-Type:application/json'];

    public function __construct(string $url, string $jwt = '')
    {
        $this->url = $url;

        if (! empty($jwt)) {
            $this->headers[] = "Authorization: Bearer {$jwt}";
        }
    }

    /**
     * @throws Exception
     */
    public function call(string $method, array $params, $id = null)
    {
        $response = $this->request($method, $params, $id);

        if (isset($response['error'])) {
            throw new Exception('Error: '.$response['error']['message']);
        } else {
            return json_decode(Crypt::decryptString($response['result']), true);
        }
    }

    private function request(string $method, array $params, $id = null)
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => Crypt::encryptString(json_encode($params, JSON_UNESCAPED_UNICODE)),
            'id' => $id,
        ];

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}
