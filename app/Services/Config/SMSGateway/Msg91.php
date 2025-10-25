<?php

namespace App\Services\Config\SMSGateway;

use App\Contracts\SMSGateway;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Msg91 implements SMSGateway
{
    private string $url;

    public function __construct()
    {
        $this->url = 'https://control.msg91.com/api/v5/flow';
    }

    private function getTemplateId(array $params = []): string
    {
        $templateId = Arr::get($params, 'template_id');

        if (! $templateId) {
            throw ValidationException::withMessages(['message' => trans('validation.required', ['attribute' => trans('config.sms.template.props.template_id')])]);
        }

        return $templateId;
    }

    public function sendSMS(array $recipient, array $params = []): void
    {
        $templateId = $this->getTemplateId($params);
        $variables = Arr::get($params, 'variables', []);

        $apiKey = config('config.sms.api_key');

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'auth-key' => $apiKey,
        ])->post($this->url, [
            'template_id' => $templateId,
            'short_url' => 1,
            'recipients' => [
                [
                    'mobiles' => Arr::get($recipient, 'mobile'),
                    ...$variables,
                ],
            ],
        ]);
    }

    public function sendBulkSMS(array $recipients, array $params = []): void
    {
        $templateId = $this->getTemplateId($params);
        $chunkSize = 20;

        $apiKey = config('config.sms.api_key');

        foreach (array_chunk($recipients, $chunkSize) as $chunk) {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'auth-key' => $apiKey,
            ])->post($this->url, [
                'template_id' => $templateId,
                'short_url' => 1,
                'recipients' => $chunk,
            ]);
        }
    }

    public function sendCustomizedSMS(array $recipients, array $params = []): void
    {
        $templateId = $this->getTemplateId($params);
        $chunkSize = 20;

        $apiKey = config('config.sms.api_key');

        foreach (array_chunk($recipients, $chunkSize) as $chunk) {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'auth-key' => $apiKey,
            ])->post($this->url, [
                'template_id' => $templateId,
                'short_url' => 1,
                'recipients' => $chunk,
            ]);
        }
    }
}
