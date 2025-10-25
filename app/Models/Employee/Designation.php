<?php

namespace App\Models\Employee;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasParent;
use App\Concerns\HasUuid;
use App\Concerns\SubordinateAccess;
use App\Models\Audience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Designation extends Model
{
    use HasFactory, HasFilter, HasMeta, HasParent, HasUuid, LogsActivity, SubordinateAccess;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'designations';

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

    public function scopeFilterAccessible(Builder $query, ?string $date = null)
    {
        $designationIds = $this->getAccessibleDesignationIds($date);

        if (is_array($designationIds)) {
            $query->whereIn('id', $designationIds);
        }
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('designation')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
