<?php

namespace Cola\Http;

use Cola\Foundation\App;
use Workerman\Protocols\Http\Response as WorkerResponse;

/**
 * Class Response
 * @package Cola\Http
 */
class Response extends WorkerResponse
{
    /**
     * @param $file
     * @return $this
     */
    public function file($file)
    {
        if ($this->notModifiedSince($file)) {
            return $this->withStatus(304);
        }
        return $this->withFile($file);
    }

    /**
     * @param $file
     * @param string $download_name
     * @return $this
     */
    public function download($file, $download_name = '')
    {
        $this->withFile($file);
        if ($download_name) {
            $this->header('Content-Disposition', "attachment; filename=\"$download_name\"");
        }
        return $this;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function notModifiedSince($file): bool
    {
        $if_modified_since = App::request()->header('if-modified-since');
        if ($if_modified_since === null || !($mtime = filemtime($file))) {
            return false;
        }
        return $if_modified_since === gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    }
}