<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ApplicationRequest;
use App\Http\Resources\Recruitment\ApplicationResource;
use App\Models\Recruitment\Application;
use App\Services\Recruitment\ApplicationListService;
use App\Services\Recruitment\ApplicationService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ApplicationService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, ApplicationListService $service)
    {
        $this->authorize('viewAny', Application::class);

        return $service->paginate($request);
    }

    public function store(ApplicationRequest $request, ApplicationService $service)
    {
        $this->authorize('create', Application::class);

        $application = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('recruitment.application.application')]),
            'application' => ApplicationResource::make($application),
        ]);
    }

    public function show(string $application, ApplicationService $service)
    {
        $application = Application::findByUuidOrFail($application);

        $this->authorize('view', $application);

        $application->load('vacancy', 'designation', 'contact', 'media');

        return ApplicationResource::make($application);
    }

    public function update(ApplicationRequest $request, string $application, ApplicationService $service)
    {
        $application = Application::findByUuidOrFail($application);

        $this->authorize('update', $application);

        $service->update($request, $application);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('recruitment.application.application')]),
        ]);
    }

    public function destroy(Request $request, string $application, ApplicationService $service)
    {
        $application = Application::findByUuidOrFail($application);

        $this->authorize('delete', $application);

        $service->deletable($request, $application);

        $application->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('recruitment.application.application')]),
        ]);
    }

    public function downloadMedia(string $application, string $uuid, ApplicationService $service)
    {
        $application = Application::findByUuidOrFail($application);

        $this->authorize('view', $application);

        return $application->downloadMedia($uuid);
    }
}
