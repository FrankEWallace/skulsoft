<?php

namespace App\Models\Calendar;

use App\Casts\DateCast;
use App\Concerns\HasDatePeriod;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Helpers\CalHelper;
use App\Models\Academic\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Holiday extends Model
{
    use HasDatePeriod, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'holidays';

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('holiday')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
