<?php

namespace App\Contracts;

use App\Models\Config\Template;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

abstract class WhatsAppTemplate
{
    public function __construct() {}

    public function getTemplateId(array $params = []): ?string
    {
        if (Arr::get($params, 'template')) {
            $template = Template::query()
                ->whereType('whatsapp')
                ->where('code', Arr::get($params, 'template'))
                ->whereNotNull('enabled_at')
                ->first();

            if (! $template) {
                return null;
            }

            return $template->getMeta('template_id');
        }

        $templateId = Arr::get($params, 'template_id');

        if (! $templateId) {
            throw ValidationException::withMessages(['message' => trans('validation.required', ['attribute' => trans('config.whatsapp.template.props.template_id')])]);
        }

        return $templateId;
    }
}
