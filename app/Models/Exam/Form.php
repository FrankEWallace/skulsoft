<?php

namespace App\Models\Exam;

use App\Casts\DateTimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Academic\Batch;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Form extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'exam_forms';

    protected $casts = [
        'confirmed_at' => DateTimeCast::class,
        'submitted_at' => DateTimeCast::class,
        'approved_at' => DateTimeCast::class,
        'meta' => 'array',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $batchIds = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->get()
            ->pluck('id')
            ->all();

        $query
            ->select('exam_forms.*')
            ->join('students', 'students.id', '=', 'exam_forms.student_id')
            ->where('students.period_id', auth()->user()->current_period_id)
            ->whereIn('students.batch_id', $batchIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->filterAccessible()
            ->where('exam_forms.uuid', $uuid)
            ->getOrFail(trans('exam.form.form'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('exam_form')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
