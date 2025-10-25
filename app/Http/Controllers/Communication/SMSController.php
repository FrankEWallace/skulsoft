<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\SMSRequest;
use App\Http\Resources\Communication\SMSResource;
use App\Models\Communication\Communication;
use App\Services\Communication\SMSListService;
use App\Services\Communication\SMSService;
use Illuminate\Http\Request;

class SMSController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, SMSService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, SMSListService $service)
    {
        $this->authorize('viewAnySMS', Communication::class);

        return $service->paginate($request);
    }

    public function store(SMSRequest $request, SMSService $service)
    {
        $this->authorize('sendSMS', Communication::class);

        $communication = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('communication.sms.sms')]),
            'sms' => SMSResource::make($communication),
        ]);
    }

    public function show(string $communication, SMSService $service)
    {
        $communication = Communication::findSMSByUuidOrFail($communication);

        $this->authorize('viewSMS', $communication);

        $communication->load([
            'audiences.audienceable',
            'media',
        ]);

        return SMSResource::make($communication);
    }

    public function downloadMedia(string $communication, string $uuid, SMSService $service)
    {
        $communication = Communication::findSMSByUuidOrFail($communication);

        $this->authorize('viewSMS', $communication);

        return $communication->downloadMedia($uuid);
    }
}
