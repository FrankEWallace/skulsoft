<?php

namespace App\Concerns;

use App\Models\Config\Config;
use App\Models\Team;
use App\Support\SetConfig;

trait SetConfigForJob
{
    public function setConfig(int $teamId, array $modules = ['general', 'system'])
    {
        $team = Team::find($teamId);

        $config = Config::query()
            ->where(function ($q) use ($teamId) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', $teamId);
            })
            ->whereIn('name', $modules)
            ->pluck('value', 'name')->all();

        (new SetConfig)->set($config);

        config(['config.team' => $team]);
    }
}
