<?php

declare(strict_types=1);

namespace Flame\Filesystem;

use Flame\Support\Facade\Log;
use OSS\Core\OssException;
use OSS\OssClient;

class AliOSSDriver implements StorageInterface
{
    private ?OssClient $ossClient = null;

    private array $config = [
        'access_key_id' => '', // OSS Key
        'access_key_secret' => '', // OSS Secret
        'bucket' => '', // OSS Bucket
        'endpoint' => '', // OSS所在区域的域名
        'asset_url' => '', // 公网可访问的自定义域名
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function instance(): OssClient
    {
        if (is_null($this->ossClient)) {
            $accessKeyId = $this->config['access_key_id'];
            $accessKeySecret = $this->config['access_key_secret'];
            $endpoint = $this->config['endpoint'];
            $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        }

        return $this->ossClient;
    }

    public function upload($object, $filePath): bool
    {
        try {
            $result = $this->instance()->uploadFile($this->config['bucket'], $object, $filePath);

            return ! empty($result);
        } catch (OssException $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    public function read($object): string
    {
        return $this->instance()->getObject($this->config['bucket'], $object);
    }

    public function write($object, $content, $option = null): bool
    {
        $result = $this->instance()->putObject($this->config['bucket'], $object, $content, $option);

        return ! empty($result);
    }

    public function append($object, $content)
    {
        try {
            return $this->instance()->appendObject($this->config['bucket'], $object, $content, 0);
        } catch (OssException $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    public function delete($object): bool
    {
        return $this->instance()->deleteObject($this->config['bucket'], $object);
    }

    public function isExists($object): bool
    {
        return $this->instance()->doesObjectExist($this->config['bucket'], $object);
    }

    public function move($oldObject, $newObject): bool
    {
        try {
            $this->instance()->copyObject($this->config['bucket'], $oldObject, $this->config['bucket'], $newObject);
            $this->instance()->deleteObject($this->config['bucket'], $oldObject);

            return true;
        } catch (OssException $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    /**
     * @throws OssException
     */
    public function url(string $url, int $timeout = 3600, array $options = []): string
    {
        $options = empty($options) ? null : $options;
        $url = $this->instance()->signUrl($this->config['bucket'], ltrim($url, '/'), $timeout, OssClient::OSS_HTTP_GET, $options);

        if (! empty($this->config['asset_url'])) {
            $endpoint = $this->config['bucket'].'.'.$this->config['endpoint'];
            $url = str_replace(['http://'.$endpoint, 'https://'.$endpoint], $this->config['asset_url'], $url);
        }

        return $url;
    }

    public function originUrl(string $signUrl): string
    {
        $parseUrls = parse_url($signUrl);

        return $parseUrls['path'] ? ltrim($parseUrls['path'], '/') : '';
    }
}
