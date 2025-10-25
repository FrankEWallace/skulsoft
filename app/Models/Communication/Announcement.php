<?php

namespace App\Models\Communication;

use App\Casts\DateTimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Academic\Period;
use App\Models\Audience;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Models\ViewLog;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Announcement extends Model
{
    use AudienceScope, HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'announcements';

    protected $attributes = [];

    protected $casts = [
        'published_at' => DateTimeCast::class,
        'is_public' => 'boolean',
        'audience' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Announcement';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'type_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'shareable');
    }

    public function viewLogs()
    {
        return $this->morphMany(ViewLog::class, 'viewable');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->can('announcement:admin-access')) {
            return;
        }

        $query->where(function ($q) {
            $q->accessible()
                ->orWhere('is_public', true);
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->getOrFail(trans('communication.announcement.announcement'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('period', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('announcement')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
