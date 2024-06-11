<?php

namespace Flame\Filesystem\Adapter;

use Flame\Filesystem\Exception\StorageException;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Throwable;

/**
 * Class QiniuAdapter
 */
class QiniuAdapter extends AdapterAbstract
{
    /**
     * @var null
     */
    protected static $instance = null;

    public static function getInstance(): ?UploadManager
    {
        if (is_null(self::$instance)) {
            static::$instance = new UploadManager();
        }

        return static::$instance;
    }

    public static function getUploadToken(): string
    {
        $config = config('filesystems.disks.qiniu');
        $auth = new Auth($config['accessKey'], $config['secretKey']);

        return $auth->uploadToken($config['bucket']);
    }

    /**
     * 上传文件
     */
    public function uploadFile(array $options = []): array
    {
        try {
            $config = config('filesystems.disks.qiniu');
            $result = [];
            foreach ($this->files as $key => $file) {
                $uniqueId = hash_file('md5', $file->getPathname());
                $saveName = $uniqueId.'.'.$file->getUploadExtension();
                $object = $config['dirname'].DIRECTORY_SEPARATOR.$saveName;
                $temp = [
                    'key' => $key,
                    'origin_name' => $file->getUploadName(),
                    'save_name' => $saveName,
                    'save_path' => $object,
                    'url' => $config['domain'].DIRECTORY_SEPARATOR.$object,
                    'unique_id' => $uniqueId,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getUploadMineType(),
                    'extension' => $file->getUploadExtension(),
                ];
                [$ret, $err] = self::getInstance()->putFile(self::getUploadToken(), $object, $file->getPathname());
                if (! empty($err)) {
                    throw new StorageException((string) $err);
                }
                array_push($result, $temp);
            }
        } catch (Throwable $exception) {
            throw new StorageException($exception->getMessage());
        }

        return $result;
    }

    /**
     * 上传本地文件
     *
     * @return mixed
     */
    public function uploadLocalFile(array $options)
    {
        throw new StorageException('暂不支持');
    }

    /**
     * 上传Base64文件
     *
     * @return mixed
     */
    public function uploadBase64(array $options)
    {
        throw new StorageException('暂不支持');
    }
}
