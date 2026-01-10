<?php

namespace Io\Billdesk\Client;

use Monolog\Logger;

class Constants
{
    const PG_PROD_BASE_URL = '';

    const CREATE_ORDER_URL = 'payments/ve1_2/orders/create';

    const CREATE_TRANSACTION_URL = '';

    const REFUND_TRANSACTION_URL = '';

    const HEADER_BD_TRACE_ID = 'BD-Traceid';

    const HEADER_BD_TIMESTAMP = 'BD-Timestamp';

    const JWE_HEADER_CLIENTID = 'clientid';

    const LOG_CHANNEL = 'billdesk-client-php';

    public static $logger;

    public static function init()
    {
        self::$logger = new Logger(Constants::LOG_CHANNEL);
    }

    public static function createOrderURL($baseUrl = Constants::PG_PROD_BASE_URL)
    {
        return $baseUrl.'/'.Constants::CREATE_ORDER_URL;
    }

    public static function createTransactionURL($baseUrl = Constants::PG_PROD_BASE_URL)
    {
        return $baseUrl.'/'.Constants::CREATE_ORDER_URL;
    }

    public static function refundTransactionURL($baseUrl = Constants::PG_PROD_BASE_URL)
    {
        return $baseUrl.'/'.Constants::CREATE_ORDER_URL;
    }
}

Constants::init();
