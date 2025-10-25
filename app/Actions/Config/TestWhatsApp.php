<?php

namespace App\Actions\Config;

use App\Actions\SendWhatsApp;
use App\Models\Config\Template;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TestWhatsApp
{
    public function execute(Request $request)
    {
        // throw ValidationException::withMessages([
        //     'message' => trans('general.errors.feature_under_development'),
        // ]);

        $testWhatsAppTemplate = Template::query()
            ->whereType('whatsapp')
            ->where('code', 'test-whatsapp-notification')
            ->firstOrFail();

        $params = [
            'template_id' => $testWhatsAppTemplate->getMeta('template_id'),
            'recipients' => [
                [
                    'mobile' => config('config.whatsapp.test_number'),
                    'message' => $testWhatsAppTemplate->content,
                    'variables' => [
                        'name' => 'Test',
                    ],
                ],
            ],
        ];

        (new SendWhatsApp)->execute($params);

        // defer(function () use ($params) {
        //     (new SendWhatsApp)->execute($params);
        // });
    }
}
