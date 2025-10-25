<?php

namespace App\Http\Controllers\Communication;

use App\Actions\UpdateViewLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\AnnouncementRequest;
use App\Http\Resources\Communication\AnnouncementResource;
use App\Models\Communication\Announcement;
use App\Services\Communication\AnnouncementListService;
use App\Services\Communication\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, AnnouncementService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, AnnouncementListService $service)
    {
        $this->authorize('viewAny', Announcement::class);

        return $service->paginate($request);
    }

    public function store(AnnouncementRequest $request, AnnouncementService $service)
    {
        $this->authorize('create', Announcement::class);

        $announcement = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('communication.announcement.announcement')]),
            'announcement' => AnnouncementResource::make($announcement),
        ]);
    }

    public function show(string $announcement, AnnouncementService $service)
    {
        $announcement = Announcement::findByUuidOrFail($announcement);

        // $this->authorize('view', $announcement);

        (new UpdateViewLog)->handle($announcement);

        if (auth()->user()->can('announcement:view-log')) {
            $announcement->load('viewLogs');
        }

        $announcement->load([
            'audiences.audienceable',
            'type',
            'employee' => fn ($q) => $q->summary(),
            'media',
        ]);

        return AnnouncementResource::make($announcement);
    }

    public function update(AnnouncementRequest $request, string $announcement, AnnouncementService $service)
    {
        $announcement = Announcement::findByUuidOrFail($announcement);

        $this->authorize('update', $announcement);

        $service->update($request, $announcement);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('communication.announcement.announcement')]),
        ]);
    }

    public function destroy(string $announcement, AnnouncementService $service)
    {
        $announcement = Announcement::findByUuidOrFail($announcement);

        $this->authorize('delete', $announcement);

        $service->deletable($announcement);

        $announcement->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('communication.announcement.announcement')]),
        ]);
    }

    public function downloadMedia(string $announcement, string $uuid, AnnouncementService $service)
    {
        $announcement = Announcement::findByUuidOrFail($announcement);

        $this->authorize('view', $announcement);

        return $announcement->downloadMedia($uuid);
    }
}
