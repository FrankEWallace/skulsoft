<?php

namespace App\Http\Controllers\Resource;

use App\Actions\UpdateViewLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\DiaryRequest;
use App\Http\Resources\Resource\DiaryResource;
use App\Models\Resource\Diary;
use App\Services\Resource\DiaryListService;
use App\Services\Resource\DiaryService;
use Illuminate\Http\Request;

class DiaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, DiaryService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, DiaryListService $service)
    {
        $this->authorize('viewAny', Diary::class);

        return $service->paginate($request);
    }

    public function store(DiaryRequest $request, DiaryService $service)
    {
        $this->authorize('create', Diary::class);

        $diary = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.diary.diary')]),
            'diary' => DiaryResource::make($diary),
        ]);
    }

    public function show(Request $request, string $diary, DiaryService $service)
    {
        $diary = Diary::findByUuidOrFail($diary);

        $this->authorize('view', $diary);

        (new UpdateViewLog)->handle($diary);

        if (auth()->user()->can('student-diary:view-log')) {
            $diary->load('viewLogs');
        }

        $diary->load(['records.subject', 'records.batch.course', 'employee' => fn ($q) => $q->summary(), 'media']);

        return DiaryResource::make($diary);
    }

    public function update(DiaryRequest $request, string $diary, DiaryService $service)
    {
        $diary = Diary::findByUuidOrFail($diary);

        $this->authorize('update', $diary);

        $service->update($request, $diary);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.diary.diary')]),
        ]);
    }

    public function destroy(string $diary, DiaryService $service)
    {
        $diary = Diary::findByUuidOrFail($diary);

        $this->authorize('delete', $diary);

        $service->deletable($diary);

        $diary->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.diary.diary')]),
        ]);
    }

    public function downloadMedia(string $diary, string $uuid, DiaryService $service)
    {
        $diary = Diary::findByUuidOrFail($diary);

        $this->authorize('view', $diary);

        return $diary->downloadMedia($uuid);
    }
}
