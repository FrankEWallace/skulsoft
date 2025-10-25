<?php

namespace App\Models\Exam;

use App\Casts\DateCast;
use App\Casts\TimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Academic\Subject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Record extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'exam_records';

    protected $casts = [
        'date' => DateCast::class,
        'start_time' => TimeCast::class,
        'marks' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getEndTimeAttribute()
    {
        if (! $this->date->value) {
            return null;
        }

        if (! $this->start_time->value) {
            return null;
        }

        if (empty($this->duration)) {
            return null;
        }

        $startTime = Carbon::parse($this->date->value.' '.$this->start_time->value);

        $endTime = $startTime->addMinutes($this->duration);

        return \Cal::time($endTime->toDateTimeString());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('exam_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
