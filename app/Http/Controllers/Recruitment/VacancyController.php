<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\VacancyRequest;
use App\Http\Resources\Recruitment\VacancyResource;
use App\Models\Recruitment\Vacancy;
use App\Services\Recruitment\VacancyListService;
use App\Services\Recruitment\VacancyService;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, VacancyService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, VacancyListService $service)
    {
        $this->authorize('viewAny', Vacancy::class);

        return $service->paginate($request);
    }

    public function store(VacancyRequest $request, VacancyService $service)
    {
        $this->authorize('create', Vacancy::class);

        $vacancy = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('recruitment.vacancy.vacancy')]),
            'vacancy' => VacancyResource::make($vacancy),
        ]);
    }

    public function show(string $vacancy, VacancyService $service)
    {
        $vacancy = Vacancy::findByUuidOrFail($vacancy);

        $this->authorize('view', $vacancy);

        $vacancy->load('records.designation', 'records.employmentType', 'media');

        return VacancyResource::make($vacancy);
    }

    public function update(VacancyRequest $request, string $vacancy, VacancyService $service)
    {
        $vacancy = Vacancy::findByUuidOrFail($vacancy);

        $this->authorize('update', $vacancy);

        $service->update($request, $vacancy);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('recruitment.vacancy.vacancy')]),
        ]);
    }

    public function destroy(Request $request, string $vacancy, VacancyService $service)
    {
        $vacancy = Vacancy::findByUuidOrFail($vacancy);

        $this->authorize('delete', $vacancy);

        $service->deletable($request, $vacancy);

        $vacancy->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('recruitment.vacancy.vacancy')]),
        ]);
    }

    public function downloadMedia(string $vacancy, string $uuid, VacancyService $service)
    {
        $vacancy = Vacancy::findByUuidOrFail($vacancy);

        $this->authorize('view', $vacancy);

        return $vacancy->downloadMedia($uuid);
    }
}
