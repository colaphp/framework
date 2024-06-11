<?php

declare(strict_types=1);

namespace Flame\JsonRpc;

use Exception;
use Flame\Support\Facade\Crypt;

abstract class JsonRpcServer
{
    public function handle(array $request): array
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $request['id'] ?? null,
        ];

        try {
            if (! isset($request['method'])) {
                throw new Exception('Method not found', -32601);
            }

            $method = $request['method'];
            if (! method_exists($this, $method)) {
                throw new Exception('Method not found', -32601);
            }

            $request['params'] = json_decode(Crypt::decryptString($request['params']), true);

            $result = $this->$method($request['params']);
            $response['result'] = Crypt::encryptString(json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            $response['error'] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }

        return $response;
    }
}
