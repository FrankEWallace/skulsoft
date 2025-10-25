<?php

namespace App\Models\Academic;

use App\Casts\TimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClassTimingSession extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'class_timing_sessions';

    protected $attributes = [];

    protected $casts = [
        'start_time' => TimeCast::class,
        'end_time' => TimeCast::class,
        'is_break' => 'boolean',
        'meta' => 'array',
    ];

    public function classTiming(): BelongsTo
    {
        return $this->belongsTo(ClassTiming::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('class_timing_session')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
