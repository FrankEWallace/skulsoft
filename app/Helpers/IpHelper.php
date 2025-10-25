<?php

namespace App\Helpers;

class IpHelper
{
    /**
     * Check if connected to internet
     */
    public static function isConnected(): bool
    {
        $connected = @fsockopen('www.google.com', 80);

        if ($connected) {
            fclose($connected);

            return true;
        }

        return false;
    }

    /**
     * Get client remote IP address
     */
    public static function getRemoteIPAddress(): ?string
    {
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * Get client local Ip address
     */
    public static function getClientIp(): string
    {
        $ips = self::getRemoteIPAddress();
        $ips = explode(',', $ips);

        return ! empty($ips[0]) ? $ips[0] : \Request::getClientIp();
    }
}
