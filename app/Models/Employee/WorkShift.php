<?php

namespace App\Models\Employee;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\WorkShift as AttendanceWorkShift;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WorkShift extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'employee_work_shifts';

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'EmployeeWorkShift';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(AttendanceWorkShift::class, 'work_shift_id');
    }

    public function getPeriodAttribute(): string
    {
        return CalHelper::getPeriod($this->start_date->value, $this->end_date->value);
    }

    public function getDurationAttribute(): string
    {
        return CalHelper::getDuration($this->start_date->value, $this->end_date->value, 'day');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
