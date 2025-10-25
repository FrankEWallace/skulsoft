<?php

namespace App\Services\Utility;

use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    public function deletable(Activity $activityLog): void
    {
        if (! \Auth::user()->is_default) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }
}
