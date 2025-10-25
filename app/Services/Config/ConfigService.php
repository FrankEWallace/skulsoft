<?php

namespace App\Services\Config;

use App\Concerns\LocalStorage;
use App\Enums\Employee\Type as EmployeeType;
use App\Enums\ServiceType;
use App\Helpers\ListHelper;
use App\Models\Config\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ConfigService
{
    use LocalStorage;

    public function getPreRequisite(Request $request)
    {
        $types = snake_case($request->type);

        $types = ! is_array($types) ? explode(',', $types) : $types;

        $data = ListHelper::getLists($types);

        $data['color_schemes'] = Arr::getList('color_schemes');

        if (in_array('countries', $types)) {
            $data['countries'] = ListHelper::getList('countries', 'code');
        }

        if (in_array('currencies', $types)) {
            $data['currencies'] = ListHelper::getList('currencies', 'name');
        }

        if (in_array('timezones', $types)) {
            $data['timezones'] = ListHelper::getList('timezones');
        }

        if (in_array('locales', $types)) {
            $data['locales'] = $this->getKey('locales');
        }

        if (in_array('services', $types)) {
            $data['services'] = ServiceType::getOptions();
        }

        if (in_array('employee_types', $types)) {
            $data['employee_types'] = EmployeeType::getOptions();
        }

        return $data;
    }

    public function getModulePreRequisite(Request $request)
    {
        $moduleConfig = Config::query()
            ->whereTeamId(auth()->user()->current_team_id)
            ->whereName('module')
            ->first();

        $modules = collect(Arr::getVar('modules'))->map(function ($module) use ($moduleConfig) {
            $systemModule = collect($moduleConfig->value ?? [])->firstWhere('name', $module['name']);

            $visibility = $systemModule['visibility'] ?? true;

            return [
                'name' => $module['name'],
                'label' => trans($module['label']),
                'position' => $systemModule['position'] ?? 0,
                'visibility' => (bool) $visibility,
                'children' => collect($module['children'] ?? [])->map(function ($child) use ($systemModule) {

                    $systemChild = collect($systemModule['children'] ?? [])->firstWhere('name', $child['name']) ?? [];

                    $visibility = $systemChild['visibility'] ?? true;

                    return [
                        'name' => $child['name'],
                        'label' => trans($child['label']),
                        'visibility' => (bool) $visibility,
                    ];
                }),
            ];
        })->sortBy('position')->values();

        return compact('modules');
    }
}
