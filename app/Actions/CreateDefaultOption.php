<?php

namespace App\Actions;

use App\Helpers\ListHelper;
use App\Models\Option;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateDefaultOption
{
    public function execute(int $teamId): void
    {
        $options = \File::json(resource_path('var/default-options.json')) ?? [];

        $colors = ListHelper::getListKey('colors');

        $options = collect($options)
            ->map(function ($option) use ($teamId, $colors) {
                return [
                    ...$option,
                    'uuid' => (string) Str::uuid(),
                    'team_id' => $teamId,
                    'meta' => json_encode([
                        'color' => Arr::random($colors),
                    ]),
                    'created_at' => now()->toDateTimeString(),
                ];
            })
            ->toArray();

        Option::insert($options);
    }
}
