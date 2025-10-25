<?php

namespace App\Models\Team;

use App\Concerns\HasFilter;
use App\Concerns\HasUuid;
use App\Http\Resources\Team\RoleResource;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends Model
{
    use HasFilter, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'roles';

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getIsDefaultAttribute()
    {
        $permission = Arr::getVar('permission');
        $roles = Arr::get($permission, 'roles', []);

        $name = Str::of($this->name)->slug('-')->value;

        if (in_array($name, $roles)) {
            return true;
        }

        return false;
    }

    public static function selectOption()
    {
        return RoleResource::collection(self::with('team')->when(! \Auth::user()->is_default, function ($q) {
            $q->whereNotIn('name', ['admin']);
        })->where(function ($q) {
            $q->whereTeamId(auth()->user()?->current_team_id)->orWhereNull('team_id');
        })->get());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('role')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
