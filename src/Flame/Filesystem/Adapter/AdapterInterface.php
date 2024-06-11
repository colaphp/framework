<?php

namespace Flame\Filesystem\Adapter;

/**
 * Class AdapterInterface
 */
interface AdapterInterface
{
    /**
     * 上传文件
     *
     * @return mixed
     */
    public function uploadFile(array $options);

    /**
     * 上传本地文件
     *
     * @return mixed
     */
    public function uploadLocalFile(array $options);

    /**
     * Base64上传文件
     *
     * @return mixed
     */
    public function uploadBase64(array $options);
}
