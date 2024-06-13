<?php

declare(strict_types=1);

namespace Flame\Http;

use Flame\Routing\RouteItem;
use Workerman\Protocols\Http\Request as WorkerRequest;

class Request extends WorkerRequest
{
    public ?string $app = null;

    public ?string $controller = null;

    public ?string $action = null;

    public ?RouteItem $route = null;

    public function all()
    {
        return $this->post() + $this->get();
    }

    public function input(string $name, $default = null)
    {
        $post = $this->post();
        if (isset($post[$name])) {
            return $post[$name];
        }
        $get = $this->get();

        return $get[$name] ?? $default;
    }

    public function only(array $keys): array
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

    public function except(array $keys)
    {
        $all = $this->all();
        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    public function file($name = null)
    {
        $files = parent::file($name);
        if ($files === null) {
            return $name === null ? [] : null;
        }
        if ($name !== null) {
            // Multi files
            if (is_array(current($files))) {
                return $this->parseFiles($files);
            }

            return $this->parseFile($files);
        }
        $uploadFiles = [];
        foreach ($files as $name => $file) {
            // Multi files
            if (is_array(current($file))) {
                $uploadFiles[$name] = $this->parseFiles($file);
            } else {
                $uploadFiles[$name] = $this->parseFile($file);
            }
        }

        return $uploadFiles;
    }

    protected function parseFile(array $file): UploadFile
    {
        return new UploadFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
    }

    protected function parseFiles(array $files): array
    {
        $uploadFiles = [];
        foreach ($files as $key => $file) {
            if (is_array(current($file))) {
                $uploadFiles[$key] = $this->parseFiles($file);
            } else {
                $uploadFiles[$key] = $this->parseFile($file);
            }
        }

        return $uploadFiles;
    }

    public function getRemoteIp(): string
    {
        return $this->connection->getRemoteIp();
    }

    public function getRemotePort(): int
    {
        return $this->connection->getRemotePort();
    }

    public function getLocalIp(): string
    {
        return $this->connection->getLocalIp();
    }

    public function getLocalPort(): int
    {
        return $this->connection->getLocalPort();
    }

    public function getRealIp(bool $safeMode = true): string
    {
        $remoteIp = $this->getRemoteIp();
        if ($safeMode && ! static::isIntranetIp($remoteIp)) {
            return $remoteIp;
        }
        $ip = $this->header('x-real-ip', $this->header('x-forwarded-for',
            $this->header('client-ip', $this->header('x-client-ip',
                $this->header('via', $remoteIp)))));
        if (is_string($ip)) {
            $ip = current(explode(',', $ip));
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $remoteIp;
    }

    public function url(): string
    {
        return '//'.$this->host().$this->path();
    }

    public function fullUrl(): string
    {
        return '//'.$this->host().$this->uri();
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isPjax(): bool
    {
        return (bool) $this->header('X-PJAX');
    }

    public function expectsJson(): bool
    {
        return ($this->isAjax() && ! $this->isPjax()) || $this->acceptJson();
    }

    public function acceptJson(): bool
    {
        return str_contains($this->header('accept', ''), 'json');
    }

    public static function isIntranetIp(string $ip): bool
    {
        // Not validate ip .
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        // Is intranet ip ? For IPv4, the result of false may not be accurate, so we need to check it manually later .
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        // Manual check only for IPv4 .
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        // Manual check .
        $reservedIps = [
            1681915904 => 1686110207, // 100.64.0.0 -  100.127.255.255
            3221225472 => 3221225727, // 192.0.0.0 - 192.0.0.255
            3221225984 => 3221226239, // 192.0.2.0 - 192.0.2.255
            3227017984 => 3227018239, // 192.88.99.0 - 192.88.99.255
            3323068416 => 3323199487, // 198.18.0.0 - 198.19.255.255
            3325256704 => 3325256959, // 198.51.100.0 - 198.51.100.255
            3405803776 => 3405804031, // 203.0.113.0 - 203.0.113.255
            3758096384 => 4026531839, // 224.0.0.0 - 239.255.255.255
        ];
        $ipLong = ip2long($ip);
        foreach ($reservedIps as $ipStart => $ipEnd) {
            if (($ipLong >= $ipStart) && ($ipLong <= $ipEnd)) {
                return true;
            }
        }

        return false;
    }
}
