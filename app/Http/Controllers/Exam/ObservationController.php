<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ObservationRequest;
use App\Http\Resources\Exam\ObservationResource;
use App\Models\Exam\Observation;
use App\Services\Exam\ObservationListService;
use App\Services\Exam\ObservationService;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, ObservationService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, ObservationListService $service)
    {
        return $service->paginate($request);
    }

    public function store(ObservationRequest $request, ObservationService $service)
    {
        $observation = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.observation.observation')]),
            'observation' => ObservationResource::make($observation),
        ]);
    }

    public function show(string $observation, ObservationService $service)
    {
        $observation = Observation::findByUuidOrFail($observation);

        $observation->load('grade');

        return ObservationResource::make($observation);
    }

    public function update(ObservationRequest $request, string $observation, ObservationService $service)
    {
        $observation = Observation::findByUuidOrFail($observation);

        $service->update($request, $observation);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.observation.observation')]),
        ]);
    }

    public function destroy(string $observation, ObservationService $service)
    {
        $observation = Observation::findByUuidOrFail($observation);

        $service->deletable($observation);

        $observation->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.observation.observation')]),
        ]);
    }
}
