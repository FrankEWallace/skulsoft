<?php

namespace App\Services\Config\WhatsAppProvider;

use App\Contracts\WhatsAppService;
use App\Contracts\WhatsAppTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Pinnacle extends WhatsAppTemplate implements WhatsAppService
{
    private string $url;

    public function __construct()
    {
        $accountId = config('config.whatsapp.account_id');
        $this->url = "https://partnersv1.pinbot.ai/v3/{$accountId}/messages";
    }

    public function sendWhatsApp(array $recipient, array $params = []): void
    {
        $templateId = $this->getTemplateId($params);

        if (! $templateId) {
            return;
        }

        $variables = Arr::get($params, 'variables', []);

        $mobile = Arr::get($recipient, 'mobile');
        $apiKey = config('config.whatsapp.api_key');

        if (config('config.whatsapp.number_prefix')) {
            $mobile = config('config.whatsapp.number_prefix').$mobile;
        }

        $variables = collect($variables)->map(function ($item) {
            return [
                'type' => 'text',
                'text' => $item,
            ];
        })->toArray();

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'apikey' => $apiKey,
        ])->withOptions([
            'verify' => config('app.env') == 'local' ? false : true,
        ])
            ->post($this->url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $mobile,
                'type' => 'template',
                'template' => [
                    'name' => $templateId,
                    'language' => [
                        'code' => 'hi',
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => $variables,
                        ],
                    ],
                ],
            ]);

        logger($response->body());

        if ($response->status() != 200) {
            throw ValidationException::withMessages(['message' => trans('communication.failed')]);
        }
    }

    public function sendBulkWhatsApp(array $recipients, array $params = []): void
    {
        // foreach (array_chunk($recipients, 20) as $chunk) {
        //     $mobile = Arr::get($chunk, 'mobile');

        //     if (config('config.whatsapp.number_prefix')) {
        //         $mobile = config('config.whatsapp.number_prefix').$mobile;
        //     }

        //     $this->callApi($this->url, [
        //         $this->receiverParam => $mobile,
        //         $this->messageParam => Arr::get($chunk, 'message'),
        //         $this->senderIdParam => config('config.whatsapp.sender_id'),
        //         $this->templateIdParam => Arr::get($params, 'template_id'),
        //         $this->templateVariableParam => Arr::get($chunk, 'variables'),
        //     ], Arr::get($chunk, 'variables'));
        // }
    }

    public function sendCustomizedWhatsApp(array $recipients, array $params = []): void
    {
        // foreach (array_chunk($recipients, 20) as $chunk) {
        //     $mobile = Arr::get($chunk, 'mobile');

        //     if (config('config.whatsapp.number_prefix')) {
        //         $mobile = config('config.whatsapp.number_prefix').$mobile;
        //     }

        //     $this->callApi($this->url, [
        //         $this->receiverParam => $mobile,
        //         $this->messageParam => Arr::get($chunk, 'message'),
        //         $this->senderIdParam => config('config.whatsapp.sender_id'),
        //         $this->templateIdParam => Arr::get($params, 'template_id'),
        //         $this->templateVariableParam => Arr::get($chunk, 'variables'),
        //     ], Arr::get($chunk, 'variables'));
        // }
    }
}
