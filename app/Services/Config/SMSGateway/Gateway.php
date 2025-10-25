<?php

namespace App\Services\Config\SMSGateway;

use App\Contracts\SMSGateway;
use InvalidArgumentException;

class Gateway
{
    public static function init(): SMSGateway
    {
        $gateway = config('config.sms.driver');

        switch ($gateway) {
            case 'twilio':
                return new Twilio;
            case 'msg91':
                return new Msg91;
            case 'custom':
                return new CustomGateway(config('config.sms.api_url'));
            default:
                throw new InvalidArgumentException(trans('config.sms.not_supported_sms_driver'));
        }
    }
}
