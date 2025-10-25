<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Communication\Announcement;
use App\Services\Communication\AnnouncementActionService;
use Illuminate\Http\Request;

class AnnouncementActionController extends Controller
{
    public function pin(Request $request, string $announcement, AnnouncementActionService $service)
    {
        $announcement = Announcement::findByUuidOrFail($announcement);

        $this->authorize('update', $announcement);

        $service->pin($request, $announcement);

        return response()->success([
            'message' => trans('global.pinned', ['attribute' => trans('communication.announcement.announcement')]),
        ]);
    }

    public function unpin(Request $request, string $announcement, AnnouncementActionService $service)
    {
        $announcement = Announcement::findByUuidOrFail($announcement);

        $this->authorize('update', $announcement);

        $service->unpin($request, $announcement);

        return response()->success([
            'message' => trans('global.unpinned', ['attribute' => trans('communication.announcement.announcement')]),
        ]);
    }
}
