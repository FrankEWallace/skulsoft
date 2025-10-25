<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\SMSTemplateRequest;
use App\Http\Resources\Config\SMSTemplateResource;
use App\Models\Config\Template;
use App\Services\Config\SMSTemplateListService;
use App\Services\Config\SMSTemplateService;
use Illuminate\Http\Request;

class SMSTemplateController extends Controller
{
    public function index(Request $request, SMSTemplateListService $service)
    {
        return $service->paginate($request);
    }

    public function show(string $uuid)
    {
        $smsTemplate = Template::query()
            ->whereUuid($uuid)
            ->whereType('sms')
            ->firstOrFail();

        request()->merge(['detail' => true]);

        return SMSTemplateResource::make($smsTemplate);
    }

    public function update(SMSTemplateRequest $request, string $uuid, SMSTemplateService $service)
    {
        $smsTemplate = Template::query()
            ->whereUuid($uuid)
            ->whereType('sms')
            ->firstOrFail();

        $service->update($request, $smsTemplate);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('config.sms.template.template')])]);
    }
}
