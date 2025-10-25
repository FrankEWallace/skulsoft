<?php

namespace App\Models\Employee;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Audience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'departments';

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function audiences()
    {
        return $this->morphToMany(Audience::class, 'audienceable');
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function scopeGlobalOrByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->where(function ($q) use ($teamId) {
            $q->whereNull('team_id')
                ->orWhere('team_id', $teamId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('department')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
