<?php

namespace App\Models\Calendar;

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
use App\Models\Incharge;
use App\Models\Option;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends Model
{
    use AudienceScope, HasDatePeriod, HasFactory, HasFilter, HasMedia, HasMeta, HasStorage, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'events';

    protected $casts = [
        'start_date' => DateCast::class,
        'start_time' => TimeCast::class,
        'end_date' => DateCast::class,
        'end_time' => TimeCast::class,
        'audience' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Event';
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

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->can('event:admin-access')) {
            return;
        }

        $query->where(function ($q) {
            $q->accessible()
                ->orWhere('is_public', '=', 1);
        });
    }

    public function scopeWithCurrentIncharges(Builder $query)
    {
        $query->with([
            'incharges', 'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithLastIncharge(Builder $query)
    {
        $query->addSelect(['incharge_id' => Incharge::select('id')
            ->whereColumn('model_id', 'events.id')
            ->where('model_type', 'Event')
            ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->getOrFail(trans('calendar.event.event'));
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
            ->useLogName('event')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
