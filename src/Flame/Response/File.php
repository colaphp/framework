<?php

declare(strict_types=1);

namespace Flame\Response;

use Exception;
use Flame\Http\Response;

class File extends Response
{
    protected int $expire = 360;

    protected string $name;

    protected string $mimeType;

    protected bool $isContent = false;

    protected bool $force = true;

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);
    }

    /**
     * 处理数据
     *
     * @throws Exception
     */
    protected function output($data)
    {
        if (! $this->isContent && ! is_file($data)) {
            throw new Exception('file not exists:'.$data);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (! empty($this->name)) {
            $name = $this->name;
        } else {
            $name = ! $this->isContent ? pathinfo($data, PATHINFO_BASENAME) : '';
        }

        if ($this->isContent) {
            $mimeType = $this->mimeType;
            $size = strlen($data);
        } else {
            $mimeType = $this->getMimeType($data);
            $size = filesize($data);
        }

        $this->header['Pragma'] = 'public';
        $this->header['Content-Type'] = $mimeType ?: 'application/octet-stream';
        $this->header['Cache-control'] = 'max-age='.$this->expire;
        $this->header['Content-Disposition'] = ($this->force ? 'attachment; ' : '').'filename="'.$name.'"';
        $this->header['Content-Length'] = $size;
        $this->header['Content-Transfer-Encoding'] = 'binary';
        $this->header['Expires'] = gmdate('D, d M Y H:i:s', time() + $this->expire).' GMT';

        $this->lastModified(gmdate('D, d M Y H:i:s', time()).' GMT');

        return $this->isContent ? $data : file_get_contents($data);
    }

    /**
     * 设置是否为内容 必须配合mimeType方法使用
     */
    public function isContent(bool $content = true): static
    {
        $this->isContent = $content;

        return $this;
    }

    /**
     * 设置有效期
     */
    public function expire(int $expire): static
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * 设置文件类型
     */
    public function mimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * 设置文件强制下载
     */
    public function force(bool $force): static
    {
        $this->force = $force;

        return $this;
    }

    /**
     * 获取文件类型信息
     */
    protected function getMimeType(string $filename): string
    {
        if (! empty($this->mimeType)) {
            return $this->mimeType;
        }

        $fInfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($fInfo, $filename);
    }

    /**
     * 设置下载文件的显示名称
     */
    public function name(string $filename, bool $extension = true): static
    {
        $this->name = $filename;

        if ($extension && ! str_contains($filename, '.')) {
            $this->name .= '.'.pathinfo($this->data, PATHINFO_EXTENSION);
        }

        return $this;
    }
}
