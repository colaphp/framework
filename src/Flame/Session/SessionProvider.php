<?php

declare(strict_types=1);

namespace Flame\Session;

use Flame\Contracts\Bootstrap;
use Workerman\Protocols\Http;
use Workerman\Protocols\Http\Session as SessionBase;
use Workerman\Worker;

class SessionProvider implements Bootstrap
{
    public static function start(?Worker $worker): void
    {
        $config = config('session');
        if (property_exists(SessionBase::class, 'name')) {
            SessionBase::$name = $config['session_name'];
        } else {
            Http::sessionName($config['session_name']);
        }
        SessionBase::handlerClass($config['handler'], $config['config'][$config['type']]);
        $map = [
            'auto_update_timestamp' => 'autoUpdateTimestamp',
            'cookie_lifetime' => 'cookieLifetime',
            'gc_probability' => 'gcProbability',
            'cookie_path' => 'cookiePath',
            'http_only' => 'httpOnly',
            'same_site' => 'sameSite',
            'lifetime' => 'lifetime',
            'domain' => 'domain',
            'secure' => 'secure',
        ];
        foreach ($map as $key => $name) {
            if (isset($config[$key]) && property_exists(SessionBase::class, $name)) {
                SessionBase::${$name} = $config[$key];
            }
        }
    }
}
