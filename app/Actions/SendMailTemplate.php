<?php

namespace App\Actions;

use App\Mail\CustomMail;
use App\Models\Config\Template;
use App\Support\MailTemplateParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendMailTemplate
{
    use MailTemplateParser;

    public function execute(string $email, string $code = null, mixed $template = null, array $variables = [], array $attachments = []): void
    {
        $mailTemplate = $template ?? Template::query()
            ->whereType('mail')
            ->whereCode($code)
            ->whereNotNull('enabled_at')
            ->first();

        if (! $mailTemplate) {
            return;
        }

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

        foreach ($variables as $key => $variable) {
            $mailTemplate->subject = Str::replace('##'.strtoupper($key).'##', $variable, $mailTemplate->subject);
            $mailTemplate->content = Str::replace('##'.strtoupper($key).'##', $variable, $mailTemplate->content);
        }

        $mailTemplate->content = $this->parse($mailTemplate->content);

        try {
            Mail::to($email)->send(new CustomMail($mailTemplate, $attachments));
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => trans('general.errors.mail_send_error')]);
        }
    }
}
