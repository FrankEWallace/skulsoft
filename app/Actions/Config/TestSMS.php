<?php

namespace App\Actions\Config;

use App\Actions\SendSMS;
use App\Models\Config\Template;
use Illuminate\Http\Request;

class TestSMS
{
    public function execute(Request $request)
    {
        $testSMSTemplate = Template::query()
            ->whereType('sms')
            ->where('code', 'test-sms-notification')
            ->firstOrFail();

        $params = [
            'template_id' => $testSMSTemplate->getMeta('template_id'),
            'recipients' => [
                [
                    'mobile' => config('config.sms.test_number'),
                    'message' => $testSMSTemplate->content,
                    'variables' => [
                        'name' => 'Test',
                    ],
                ],
            ],
        ];

        (new SendSMS)->execute($params);

        // defer(function () use ($params) {
        //     (new SendSMS)->execute($params);
        // });
    }
}
