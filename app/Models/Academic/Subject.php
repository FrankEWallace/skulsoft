<?php

namespace App\Models\Academic;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subject extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'subjects';

    protected $attributes = [];

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'type_id');
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('period', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeWithSubjectRecordByCourse(Builder $query, int $courseId)
    {
        $batchIds = Batch::query()
            ->where('course_id', '=', $courseId)
            ->pluck('id')
            ->all();

        $query
            ->select('subjects.*', 'subject_records.credit', 'subject_records.is_elective', 'subject_records.max_class_per_week', 'subject_records.has_no_exam', 'subject_records.has_grading', 'subject_records.position as subject_record_position')
            ->join('subject_records', function ($join) {
                $join->on('subject_records.subject_id', '=', 'subjects.id');
            })
            ->where(function ($q) use ($batchIds, $courseId) {
                $q->whereIn('subject_records.batch_id', $batchIds)
                    ->orWhere('subject_records.course_id', '=', $courseId);
            });
    }

    public function scopeWithSubjectRecord(Builder $query, int $batchId, int $courseId)
    {
        $query
            ->select('subjects.*', 'subject_records.credit', 'subject_records.is_elective', 'subject_records.max_class_per_week', 'subject_records.has_no_exam', 'subject_records.has_grading', 'subject_records.position as subject_record_position')
            ->join('subject_records', function ($join) {
                $join->on('subject_records.subject_id', '=', 'subjects.id');
            })
            ->where(function ($q) use ($batchId, $courseId) {
                $q->where('subject_records.batch_id', '=', $batchId)
                    ->orWhere('subject_records.course_id', '=', $courseId);
            });
    }

    public function scopeFindByCourseOrFail(Builder $query, int $courseId, ?string $subjectUuid = null)
    {
        return $query
            ->withSubjectRecordByCourse($courseId)
            ->where('subjects.uuid', $subjectUuid)
            ->getOrFail(trans('academic.subject.subject'));
    }

    public function scopeFindByBatchOrFail(Builder $query, int $batchId, int $courseId, ?string $subjectUuid = null)
    {
        return $query
            ->withSubjectRecord($batchId, $courseId)
            ->where('subjects.uuid', $subjectUuid)
            ->getOrFail(trans('academic.subject.subject'));
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function scopeFindByUuidOrFail(Builder $query, $uuid)
    {
        return $query
            ->byPeriod()
            ->whereUuid($uuid)
            ->getOrFail(trans('academic.subject.subject'), 'message');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('subject')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
