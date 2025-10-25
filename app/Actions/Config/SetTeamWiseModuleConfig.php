<?php

namespace App\Actions\Config;

use App\Models\Config\Config;

class SetTeamWiseModuleConfig
{
    public function execute(int $teamId, string $module)
    {
        $config = Config::query()
            ->where('team_id', $teamId)
            ->where('name', $module)
            ->value('value');

        if (empty($config)) {
            return;
        }

        $defaultConfig = \File::json(resource_path('var/config.json'))[$module] ?? [];

        $transformedConfig = [];
        foreach ($defaultConfig as $item) {
            $transformedConfig[$item['name']] = $item['value'];
        }

        $mergedConfig = array_merge($transformedConfig, $config);

        config([
            'config.'.$module => $mergedConfig,
        ]);
    }
}
