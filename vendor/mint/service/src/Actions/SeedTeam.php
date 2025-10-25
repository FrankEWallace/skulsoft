<?php

namespace Mint\Service\Actions;

use App\Helpers\SysHelper;
use App\Models\Team;
use Closure;
use Illuminate\Support\Str;

class SeedTeam
{
    public function handle($params, Closure $next)
    {

        $team = Team::forceCreate([
            'name' => 'Default'
        ]);

        SysHelper::setTeam($team->id);

        return $next($params);
    }
}
