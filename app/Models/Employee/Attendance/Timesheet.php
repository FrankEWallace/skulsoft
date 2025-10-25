<?php

namespace App\Models\Employee\Attendance;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Employee\Employee;
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

    protected $table = 'timesheets';

    protected $casts = [
        'date' => DateCast::class,
        'in_at' => DateTimeCast::class,
        'out_at' => DateTimeCast::class,
        'meta' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->detail()])
            ->getOrFail(trans('employee.attendance.timesheet.timesheet'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('timesheet')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
