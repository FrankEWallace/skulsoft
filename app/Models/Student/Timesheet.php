<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\TimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Timesheet extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'student_timesheets';

    protected $casts = [
        'date' => DateCast::class,
        'in_at' => TimeCast::class,
        'out_at' => TimeCast::class,
        'meta' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->whereUuid($uuid)
            ->with(['student' => fn ($q) => $q->detail()])
            ->getOrFail(trans('student.timesheet.timesheet'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('student_timesheet')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
