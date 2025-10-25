<?php

namespace App\Actions\Config;

use App\Helpers\ListHelper;

class StoreMailConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'driver' => 'required|in:'.implode(',', ListHelper::getListKey('mail_drivers')),
            'from_name' => 'required',
            'from_address' => 'required',
            'smtp_host' => 'required_if:driver,smtp',
            'smtp_port' => 'nullable|required_if:driver,smtp|integer',
            'smtp_username' => 'required_if:driver,smtp',
            'smtp_password' => 'required_if:driver,smtp',
            'smtp_encryption' => 'nullable|required_if:driver,smtp|in:ssl,tls,SSL,TLS',
            'mailgun_domain' => 'required_if:driver,mailgun',
            'mailgun_secret' => 'required_if:driver,mailgun',
            'mailgun_endpoint' => 'required_if:driver,mailgun',
        ], [
            'smtp_host.required_if' => __('validation.required', ['attribute' => __('config.mail.props.smtp_host')]),
            'smtp_port.required_if' => __('validation.required', ['attribute' => __('config.mail.props.smtp_port')]),
            'smtp_username.required_if' => __('validation.required', ['attribute' => __('config.mail.props.smtp_username')]),
            'smtp_password.required_if' => __('validation.required', ['attribute' => __('config.mail.props.smtp_password')]),
            'smtp_encryption.required_if' => __('validation.required', ['attribute' => __('config.mail.props.smtp_encryption')]),
            'mailgun_domain.required_if' => __('validation.required', ['attribute' => __('config.mail.props.mailgun_domain')]),
            'mailgun_secret.required_if' => __('validation.required', ['attribute' => __('config.mail.props.mailgun_secret')]),
            'mailgun_endpoint.required_if' => __('validation.required', ['attribute' => __('config.mail.props.mailgun_endpoint')]),
        ], [
            'from_name' => __('config.mail.props.from_name'),
            'from_address' => __('config.mail.props.from_address'),
        ]);

        return $input;
    }
}
