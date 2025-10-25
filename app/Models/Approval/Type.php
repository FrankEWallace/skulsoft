<?php

namespace App\Models\Approval;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Approval\Category;
use App\Models\Employee\Department;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Type extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'approval_types';

    protected $casts = [
        'category' => Category::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'ApprovalType';
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class, 'type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getStatusAttribute(): array
    {
        if ($this->getConfig('is_active', true)) {
            return [
                'label' => trans('approval.type.statuses.active'),
                'color' => 'success',
            ];
        }

        return [
            'label' => trans('approval.type.statuses.inactive'),
            'color' => 'danger',
        ];
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->byTeam()
            ->where('uuid', $uuid)
            ->getOrFail(trans('approval.type.type'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('approval_type')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
