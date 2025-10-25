<?php

namespace App\Services\Communication;

use App\Models\Communication\Announcement;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnnouncementActionService
{
    public function pin(Request $request, Announcement $announcement): void
    {
        if ($announcement->getMeta('pinned_at')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $announcement->setMeta([
            'pinned_at' => now()->toDateTimeString(),
        ]);
        $announcement->save();
    }

    public function unpin(Request $request, Announcement $announcement): void
    {
        if (empty($announcement->getMeta('pinned_at'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $announcement->setMeta([
            'pinned_at' => null,
        ]);
        $announcement->save();
    }
}
