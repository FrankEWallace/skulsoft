<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\WhatsAppRequest;
use App\Http\Resources\Communication\WhatsAppResource;
use App\Models\Communication\Communication;
use App\Services\Communication\WhatsAppListService;
use App\Services\Communication\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, WhatsAppService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, WhatsAppListService $service)
    {
        $this->authorize('viewAnyWhatsApp', Communication::class);

        return $service->paginate($request);
    }

    public function store(WhatsAppRequest $request, WhatsAppService $service)
    {
        $this->authorize('sendWhatsApp', Communication::class);

        $communication = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('communication.whatsapp.whatsapp')]),
            'whatsapp' => WhatsAppResource::make($communication),
        ]);
    }

    public function show(string $communication, WhatsAppService $service)
    {
        $communication = Communication::findWhatsAppByUuidOrFail($communication);

        $this->authorize('viewWhatsApp', $communication);

        $communication->load([
            'audiences.audienceable',
            'media',
        ]);

        return WhatsAppResource::make($communication);
    }

    public function downloadMedia(string $communication, string $uuid, WhatsAppService $service)
    {
        $communication = Communication::findWhatsAppByUuidOrFail($communication);

        $this->authorize('viewWhatsApp', $communication);

        return $communication->downloadMedia($uuid);
    }
}
