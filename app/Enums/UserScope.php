<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum UserScope: string
{
    use HasEnum;

    case ALL_TEAMS = 'all_teams';
    case ORGANIZATION_WISE = 'organization_wise';
    case MULTIPLE_TEAMS = 'multiple_teams';
    case CURRENT_TEAM = 'current_team';

    public static function translation(): string
    {
        return 'user.scopes.';
    }
}
