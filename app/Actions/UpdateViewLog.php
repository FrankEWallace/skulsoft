<?php

namespace App\Actions;

use App\Models\ViewLog;
use Illuminate\Database\Eloquent\Model;

class UpdateViewLog
{
    public function handle(Model $model, bool $forStudentOrGuardian = true): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if ($forStudentOrGuardian && ! $user->hasAnyRole(['student', 'guardian'])) {
            return;
        }

        $ipAddress = request()->ip();

        $recentView = ViewLog::where('user_id', $user->id)
            ->where('viewable_type', $model->getMorphClass())
            ->where('viewable_id', $model->id)
            ->where('ip_address', $ipAddress)
            ->where('viewed_at', '>=', now()->subMinutes(10))
            ->first();

        if ($recentView) {
            return;
        }

        ViewLog::create([
            'user_id' => $user->id,
            'viewable_type' => $model->getMorphClass(),
            'viewable_id' => $model->id,
            'ip_address' => $ipAddress,
            'viewed_at' => now()->toDateTimeString(),
        ]);
    }
}
