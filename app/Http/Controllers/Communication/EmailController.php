<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\EmailRequest;
use App\Http\Resources\Communication\EmailResource;
use App\Models\Communication\Communication;
use App\Services\Communication\EmailListService;
use App\Services\Communication\EmailService;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, EmailService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, EmailListService $service)
    {
        $this->authorize('viewAnyEmail', Communication::class);

        return $service->paginate($request);
    }

    public function store(EmailRequest $request, EmailService $service)
    {
        $this->authorize('sendEmail', Communication::class);

        $communication = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('communication.email.email')]),
            'email' => EmailResource::make($communication),
        ]);
    }

    public function show(string $communication, EmailService $service)
    {
        $communication = Communication::findEmailByUuidOrFail($communication);

        $this->authorize('viewEmail', $communication);

        $communication->load([
            'audiences.audienceable',
            'media',
        ]);

        return EmailResource::make($communication);
    }

    public function downloadMedia(string $communication, string $uuid, EmailService $service)
    {
        $communication = Communication::findEmailByUuidOrFail($communication);

        $this->authorize('viewEmail', $communication);

        return $communication->downloadMedia($uuid);
    }
}
