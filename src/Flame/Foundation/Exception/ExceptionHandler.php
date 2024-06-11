<?php

declare(strict_types=1);

namespace Flame\Foundation\Exception;

use Flame\Foundation\Contract\ExceptionHandlerInterface;
use Flame\Http\Request;
use Flame\Http\Response;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionHandler implements ExceptionHandlerInterface
{
    protected ?LoggerInterface $logger = null;

    protected bool $debug = false;

    public array $dontReport = [];

    public function __construct($logger, $debug)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function report(Throwable $e): void
    {
        if ($this->shouldntReport($e)) {
            return;
        }
        $logs = '';
        if ($request = \request()) {
            $logs = $request->getRealIp().' '.$request->method().' '.trim($request->fullUrl(), '/');
        }
        $this->logger->error($logs.PHP_EOL.$e);
    }

    public function render(Request $request, Throwable $e): Response
    {
        $code = $e->getCode();
        if ($request->expectsJson()) {
            $json = ['code' => $code ?: 500, 'msg' => $this->debug ? $e->getMessage() : 'Server internal error', 'data' => null];
            $this->debug && $json['traces'] = (string) $e;

            return new Response(200, ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        $error = $this->debug ? nl2br((string) $e) : 'Server internal error';

        return new Response(500, [], $error);
    }

    protected function shouldntReport(Throwable $e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    public function __get(string $name)
    {
        if ($name === '_debug') {
            return $this->debug;
        }

        return null;
    }
}
