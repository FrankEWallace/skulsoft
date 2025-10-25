<?php

namespace App\Actions\Config;

use Closure;

class SetMailConfig
{
    public function handle($config, Closure $next)
    {
        if (app()->environment('local')) {
            return $next($config);
        }

        config([
            'mail.from.address' => config('config.mail.from_address'),
            'mail.from.name' => config('config.mail.from_name'),
            'mail.default' => config('config.mail.driver'),
        ]);

        if (config('config.mail.driver') === 'mailgun') {
            config([
                'services.mailgun.domain' => config('config.mail.mailgun_domain'),
                'services.mailgun.secret' => config('config.mail.mailgun_secret'),
                'services.mailgun.endpoint' => config('config.mail.mailgun_endpoint'),
            ]);
        } elseif (config('config.mail.driver') === 'smtp') {
            config([
                'mail.mailers.smtp.host' => config('config.mail.smtp_host'),
                'mail.mailers.smtp.port' => config('config.mail.smtp_port'),
                'mail.mailers.smtp.encryption' => strtolower(config('config.mail.smtp_encryption')),
                'mail.mailers.smtp.username' => config('config.mail.smtp_username'),
                'mail.mailers.smtp.password' => config('config.mail.smtp_password'),
            ]);
        }

        return $next($config);
    }
}
