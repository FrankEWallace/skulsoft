<?php

namespace App\Concerns;

use App\Models\Contact;

trait HasViewLogs
{
    public function getViewLogs()
    {
        if (! $this->relationLoaded('viewLogs')) {
            return [];
        }

        $contacts = Contact::query()
            ->whereIn('user_id', $this->viewLogs->pluck('user_id')->all())
            ->get();

        return $this->viewLogs->groupBy(function ($log) use ($contacts) {
            return $contacts->firstWhere('user_id', $log->user_id)?->name ?? '-';
        })->map(function ($logs, $key) {
            $lastViewedAt = $logs->max('viewed_at');

            return [
                'name' => $key,
                'last_viewed_at' => \Cal::dateTime($lastViewedAt),
                'details' => $logs->map(function ($log) {
                    return [
                        'viewed_at' => $log->viewed_at,
                        'ip_address' => $log->ip_address,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }
}
