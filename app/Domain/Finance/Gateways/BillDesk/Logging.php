<?php

namespace Io\Billdesk\Client;

use Monolog\Handler\Handler;
use Monolog\Logger;

class Logging
{
    private static $handlers;

    private static $defaultLogger;

    public static function init()
    {
        self::$handlers = [];
        self::$defaultLogger = new Logger(Constants::LOG_CHANNEL);
    }

    public static function addHandler(Handler $handler)
    {
        self::$defaultLogger->pushHandler($handler);
        array_push(self::$handlers, $handler);
    }

    public static function getLogger(string $channel)
    {
        $logger = new Logger($channel);
        foreach (self::$handlers as $handler) {
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    public static function getDefaultLogger()
    {
        return self::$defaultLogger;
    }
}

Logging::init();
