<?php

namespace Mint\Service\Actions;

use App\Helpers\SysHelper;
use Closure;
use Illuminate\Support\Arr;

class SetENV
{
    public function handle($params, Closure $next)
    {
        if (env('APP_ENV') === 'testing') {
            return $next($params);
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

        $host = Arr::get(parse_url($_SERVER['HTTP_HOST']), 'path');
        $isIP = filter_var($host, FILTER_VALIDATE_IP) ? true : false;
        $host = str_replace('www.', '', $host);
        $sessionDomain = $isIP ? $host : ('.' . $host);
        $sanctumStatefulDomain = $isIP ? $host : ($host . ',www.' . $host);

        SysHelper::setEnv([
            'APP_URL'                  => $protocol . $host,
            'DB_PORT'                  => Arr::get($params, 'db_port'),
            'DB_HOST'                  => Arr::get($params, 'db_host'),
            'DB_DATABASE'              => Arr::get($params, 'db_name'),
            'DB_USERNAME'              => Arr::get($params, 'db_username'),
            'DB_PASSWORD'              => Arr::get($params, 'db_password'),
            'SESSION_DOMAIN'           => $sessionDomain,
            'SANCTUM_STATEFUL_DOMAINS' => $sanctumStatefulDomain,
        ]);

        config(['app.env' => 'local']);
        config(['telescope.enabled' => false]);

        \DB::purge('mysql');

        config([
            'database.connections.mysql.host' => Arr::get($params, 'db_host'),
            'database.connections.mysql.port' => Arr::get($params, 'db_port'),
            'database.connections.mysql.database' => Arr::get($params, 'db_name'),
            'database.connections.mysql.username' => Arr::get($params, 'db_username'),
            'database.connections.mysql.password' => Arr::get($params, 'db_password')
        ]);

        \DB::reconnect('mysql');

        return $next($params);
    }
}
