<?php

namespace App\Services\Utility;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class BackupService
{
    public function deletable($uuid = null): void
    {
        if (! \Auth::user()->is_default) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function delete($uuid = null): void
    {
        $disk = Arr::first(config('backup.backup.destination.disks', []));

        if (! \Storage::disk($disk)->exists('backup/'.$uuid)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \Storage::disk($disk)->delete('backup/'.$uuid);
    }
}
