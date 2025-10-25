<?php

namespace App\Actions;

use App\Services\Config\SMSGateway\Gateway;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SendSMS
{
    public function execute(array $params = []): void
    {
        $recipients = Arr::get($params, 'recipients', []);

        if (! count($recipients)) {
            throw ValidationException::withMessages(['message' => trans('communication.sms.no_recipient_found')]);
        }

        $gateway = Gateway::init();

        if (count($recipients) == 1) {
            $variables = Arr::get($recipients[0], 'variables', []);
            $variables = $this->addGeneralVariable($variables);

            $gateway->sendSMS(recipient: $recipients[0], params: [
                'template_id' => Arr::get($params, 'template_id'),
                'variables' => $variables,
            ]);

            return;
        }

        $message = collect($recipients)->unique('message')->count();

        if ($message == 1) {
            $gateway->sendBulkSMS(recipients: $recipients, params: [
                'template_id' => Arr::get($params, 'template_id'),
            ]);

            return;
        }

        $gateway->sendCustomizedSMS(recipients: $recipients, params: [
            'template_id' => Arr::get($params, 'template_id'),
        ]);
    }

    private function addGeneralVariable(array $variables = []): array
    {
        $variables['app_name'] = config('config.team.name');
        $variables['app_email'] = config('config.team.config.email');
        $variables['app_phone'] = config('config.team.config.phone');
        $variables['app_address'] = Arr::toAddress([
            'address_line1' => config('config.team.config.address_line1'),
            'address_line2' => config('config.team.config.address_line2'),
            'city' => config('config.team.config.city'),
            'state' => config('config.team.config.state'),
            'country' => config('config.team.config.country'),
            'zipcode' => config('config.team.config.zipcode'),
        ]);

        return $variables;
    }
}
