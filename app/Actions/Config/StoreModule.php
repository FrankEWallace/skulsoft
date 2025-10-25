<?php

namespace App\Actions\Config;

use App\Models\Config\Config;
use Illuminate\Support\Arr;

class StoreModule
{
    public function execute(array $data)
    {
        $moduleConfig = Config::query()
            ->whereTeamId(auth()->user()->current_team_id)
            ->whereName('module')
            ->first();

        $data = collect($data['modules'] ?? []);

        $modules = collect(Arr::getVar('modules'))->map(function ($module) use ($data, $moduleConfig) {
            $systemModule = collect($moduleConfig->value ?? [])->firstWhere('name', $module['name']) ?? [];

            $inputModule = $data->firstWhere('name', $module['name']) ?? [];

            $index = $data->search(function ($item) use ($module) {
                return $item['name'] === $module['name'];
            });

            $visibility = array_key_exists('visibility', $inputModule) ? $inputModule['visibility'] : ($systemModule['visibility'] ?? true);

            return [
                'name' => $module['name'],
                'position' => $index,
                'visibility' => (bool) $visibility,
                'children' => collect($module['children'] ?? [])->map(function ($child) use ($inputModule, $systemModule) {

                    $systemChild = collect($systemModule['children'] ?? [])->firstWhere('name', $child['name']) ?? [];

                    $inputChild = collect($inputModule['children'] ?? [])->firstWhere('name', $child['name']) ?? [];

                    $visibility = array_key_exists('visibility', $inputChild) ? $inputChild['visibility'] : ($systemChild['visibility'] ?? true);

                    return [
                        'name' => $child['name'],
                        'visibility' => (bool) $visibility,
                    ];
                }),
            ];
        });

        Config::updateOrCreate([
            'team_id' => auth()->user()->current_team_id,
            'name' => 'module',
        ], [
            'value' => $modules,
        ]);
    }
}
