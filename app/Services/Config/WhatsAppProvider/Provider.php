<?php

namespace App\Services\Config\WhatsAppProvider;

use App\Contracts\WhatsAppService;
use InvalidArgumentException;

class Provider
{
    public static function init(): WhatsAppService
    {
        $provider = config('config.whatsapp.provider');

        switch ($provider) {
            case 'pinnacle':
                return new Pinnacle;
            case 'msg91':
                return new Msg91;
            case 'isms_my':
                return new IsmsMy;
            case 'custom':
                return new CustomProvider(config('config.whatsapp.api_url'));
            default:
                throw new InvalidArgumentException(trans('config.whatsapp.not_supported_whatsapp_provider'));
        }
    }
}
