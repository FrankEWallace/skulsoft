<?php

namespace App\Contracts;

interface SMSGateway
{
    // send single sms
    public function sendSMS(array $recipient, array $params = []): void;

    // send same sms to multiple recipients
    public function sendBulkSMS(array $recipients, array $params = []): void;

    // send customized sms to multiple recipients
    public function sendCustomizedSMS(array $recipients, array $params = []): void;
}
