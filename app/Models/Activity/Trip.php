<?php

namespace App\Models\Activity;

use App\Casts\DateCast;
use App\Casts\TimeCast;
use App\Concerns\HasDatePeriod;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasUuid;
use App\Helpers\CalHelper;
use App\Models\Academic\Period;
use App\Models\Audience;
use App\Models\Option;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Trip extends Model
{
    use AudienceScope, HasDatePeriod, HasFactory, HasFilter, HasMedia, HasMeta, HasStorage, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'trips';

    protected $casts = [
        'start_date' => DateCast::class,
        'start_time' => TimeCast::class,
        'end_date' => DateCast::class,
        'end_time' => TimeCast::class,
        'audience' => 'array',
        'fees' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Trip';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'type_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'shareable');
    }

    public function participants()
    {
        return $this->hasMany(TripParticipant::class);
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->can('trip:manage')) {
            return;
        }

        $query->accessible();
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->getOrFail(trans('activity.trip.trip'));
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

    public function getDurationAttribute(): string
    {
        return CalHelper::getDuration($this->start_date->value, $this->end_date->value, 'day');
    }

    public function getDurationInDetailAttribute(): string
    {
        $duration = $this->start_date->formatted;

        if ($this->start_time) {
            $duration .= ' '.$this->start_time->formatted;
        }

        if ($this->end_date) {
            $duration .= ' - '.$this->end_date->formatted;
        }

        if ($this->end_time) {
            $duration .= ' '.$this->end_time->formatted;
        }

        return $duration;
    }

    protected function getCoverImageAttribute(): string
    {
        $cover = $this->getMeta('assets.cover');

        return $this->getImageFile(visibility: 'public', path: $cover, default: '/images/item/cover.webp');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('trip')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
