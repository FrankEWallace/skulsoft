<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasNotification;
use App\Concerns\HasUuid;
use App\Enums\Student\AttendanceSession;
use App\Models\Academic\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Attendance extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, HasNotification, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'student_attendances';

    protected $casts = [
        'session' => AttendanceSession::class,
        'date' => DateCast::class,
        'is_default' => 'boolean',
        'values' => 'array',
        'meta' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereUuid($uuid)
            ->getOrFail(trans('student.attendance.attendance'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('student_attendance')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
