<?php

declare(strict_types=1);

namespace Flame\Http;

use Flame\Foundation\App;
use Throwable;
use Workerman\Protocols\Http\Response as WorkerResponse;

class Response extends WorkerResponse
{
    protected ?Throwable $exception = null;

    /**
     * File
     */
    public function file(string $file): Response
    {
        if ($this->notModifiedSince($file)) {
            return $this->withStatus(304);
        }

        return $this->withFile($file);
    }

    /**
     * Download
     */
    public function download(string $file, string $downloadName = ''): Response
    {
        $this->withFile($file);
        if ($downloadName) {
            $this->header('Content-Disposition', "attachment; filename=\"$downloadName\"");
        }

        return $this;
    }

    /**
     * NotModifiedSince
     */
    protected function notModifiedSince(string $file): bool
    {
        $ifModifiedSince = App::request()->header('if-modified-since');
        if ($ifModifiedSince === null || ! is_file($file) || ! ($mtime = filemtime($file))) {
            return false;
        }

        return $ifModifiedSince === gmdate('D, d M Y H:i:s', $mtime).' GMT';
    }

    /**
     * Exception
     */
    public function exception(?Throwable $exception = null): ?Throwable
    {
        if ($exception) {
            $this->exception = $exception;
        }

        return $this->exception;
    }
}
