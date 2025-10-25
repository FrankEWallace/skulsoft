<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SessionRequest;
use App\Http\Resources\Academic\SessionResource;
use App\Services\Academic\SessionListService;
use App\Services\Academic\SessionService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:session:manage');
    }

    public function preRequisite(SessionService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, SessionListService $service)
    {
        return $service->paginate($request);
    }

    public function store(SessionRequest $request, SessionService $service)
    {
        $session = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.session.session')]),
            'session' => SessionResource::make($session),
        ]);
    }

    public function show(string $session, SessionService $service): SessionResource
    {
        $session = $service->findByUuidOrFail($session);

        return SessionResource::make($session);
    }

    public function update(SessionRequest $request, string $session, SessionService $service)
    {
        $session = $service->findByUuidOrFail($session);

        $service->update($request, $session);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.session.session')]),
        ]);
    }

    public function destroy(string $session, SessionService $service)
    {
        $session = $service->findByUuidOrFail($session);

        $service->deletable($session);

        $session->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.session.session')]),
        ]);
    }
}
