<?php

namespace App\Services\Config\WhatsAppProvider;

use App\Contracts\WhatsAppService;
use App\Contracts\WhatsAppTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class Msg91 extends WhatsAppTemplate implements WhatsAppService
{
    private string $url;

    public function __construct()
    {
        $this->url = 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/';
    }

    public function sendWhatsApp(array $recipient, array $params = []): void
    {
        $templateId = $this->getTemplateId($params);

        if (! $templateId) {
            return;
        }

        // $variables = Arr::get($params, 'variables', []);

        // $mobile = Arr::get($recipient, 'mobile');
        // $apiKey = config('config.sms.api_key');

        // if (config('config.whatsapp.number_prefix')) {
        //     $mobile = config('config.whatsapp.number_prefix').$mobile;
        // }

        // $this->callApi($this->url, [
        //     $this->receiverParam => $mobile,
        //     $this->messageParam => Arr::get($recipient, 'message'),
        //     $this->senderIdParam => config('config.whatsapp.sender_id'),
        //     $this->templateIdParam => Arr::get($params, 'template_id'),
        //     $this->templateVariableParam => Arr::get($params, 'variables'),
        // ], Arr::get($params, 'variables'));
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

    private function callApi(string $url, array $data = [], array $variables = [])
    {
        // $message = Arr::get($data, $this->messageParam);

        // foreach ($variables as $key => $value) {
        //     $message = str_replace('##'.strtoupper($key).'##', $value, $message);
        // }

        // Arr::set($data, $this->messageParam, $message);

        // $data = collect($data)->filter(function ($item) {
        //     return ! empty($item);
        // })->toArray();

        // $additionalParams = $this->additionalParams ? collect(explode(',', $this->additionalParams))
        //     ->mapWithKeys(function ($item) {
        //         $key = explode('::', $item)[0];
        //         $value = explode('::', $item)[1];

        //         return [$key => $value];
        //     })->toArray() : [];

        // $apiHeaders = $this->apiHeaders ? collect(explode(',', $this->apiHeaders))
        //     ->mapWithKeys(function ($item) {
        //         $key = explode('::', $item)[0];
        //         $value = explode('::', $item)[1];

        //         return [$key => $value];
        //     })->toArray() : [];

        // $data = array_merge($data, $additionalParams);

        // if ($this->method == 'GET') {
        //     $response = Http::withHeaders($apiHeaders)
        //         ->withOptions([
        //             'verify' => config('app.env') == 'local' ? false : true,
        //         ])
        //         ->get($url, $data);
        // } else {
        //     $response = Http::withHeaders($apiHeaders)
        //         ->withOptions([
        //             'verify' => config('app.env') == 'local' ? false : true,
        //         ])
        //         ->post($url, $data);
        // }

        // return $response->json();
    }
}
