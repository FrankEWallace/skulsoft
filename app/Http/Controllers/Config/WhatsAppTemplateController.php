<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\WhatsAppTemplateRequest;
use App\Http\Resources\Config\WhatsAppTemplateResource;
use App\Models\Config\Template;
use App\Services\Config\WhatsAppTemplateListService;
use App\Services\Config\WhatsAppTemplateService;
use Illuminate\Http\Request;

class WhatsAppTemplateController extends Controller
{
    public function index(Request $request, WhatsAppTemplateListService $service)
    {
        return $service->paginate($request);
    }

    public function show(string $uuid)
    {
        $whatsAppTemplate = Template::query()
            ->whereUuid($uuid)
            ->whereType('whatsapp')
            ->firstOrFail();

        request()->merge(['detail' => true]);

        return WhatsAppTemplateResource::make($whatsAppTemplate);
    }

    public function update(WhatsAppTemplateRequest $request, string $uuid, WhatsAppTemplateService $service)
    {
        $whatsAppTemplate = Template::query()
            ->whereUuid($uuid)
            ->whereType('whatsapp')
            ->firstOrFail();

        $service->update($request, $whatsAppTemplate);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('config.whatsapp.template.template')])]);
    }
}
