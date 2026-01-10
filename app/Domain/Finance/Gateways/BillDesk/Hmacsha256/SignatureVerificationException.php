<?php

namespace Io\Billdesk\Client\Hmacsha256;

use Exception;

class SignatureVerificationException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
