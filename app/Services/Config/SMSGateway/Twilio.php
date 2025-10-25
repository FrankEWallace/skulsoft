<?php

namespace App\Services\Config\SMSGateway;

use App\Contracts\SMSGateway;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client;

class Twilio implements SMSGateway
{
    public function __construct(private Client $client)
    {
        $this->client = new Client(config('config.sms.api_key'), config('config.sms.api_secret'));
    }

    public function sendSMS(array $recipient, array $params = []): void
    {
        $mobile = Arr::get($recipient, 'mobile');
        $message = Arr::get($recipient, 'message');

        try {
            $response = $this->client->messages->create($mobile, [
                'from' => config('config.sms.sender_id'),
                'body' => $message,
            ]);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => $e->getMessage()]);
        }
    }

    public function sendBulkSMS(array $recipients, array $params = []): void
    {
        try {
            foreach ($recipients as $recipient) {
                $response = $this->client->messages->create(Arr::get($recipient, 'mobile'), [
                    'from' => config('config.sms.sender_id'),
                    'body' => Arr::get($recipient, 'message'),
                ]);
            }
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => $e->getMessage()]);
        }
    }

    public function sendCustomizedSMS(array $recipients, array $params = []): void
    {
        try {
            foreach ($recipients as $recipient) {
                $response = $this->client->messages->create(Arr::get($recipient, 'mobile'), [
                    'from' => config('config.sms.sender_id'),
                    'body' => Arr::get($recipient, 'message'),
                ]);
            }
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => $e->getMessage()]);
        }
    }
}
