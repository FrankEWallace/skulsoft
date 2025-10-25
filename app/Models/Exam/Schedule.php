<?php

namespace App\Models\Exam;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Exam\AssessmentAttempt;
use App\Models\Academic\Batch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Schedule extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'exam_schedules';

    protected $casts = [
        'is_reassessment' => 'boolean',
        'attempt' => AssessmentAttempt::class,
        'details' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class, 'observation_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class)->orderBy('date', 'asc');
    }

    public function getHasFormAttribute(): bool
    {
        return (bool) $this->getMeta('has_form');
    }

    public function getMarksheetStatusAttribute(): array
    {
        $status = Arr::get($this->config, 'marksheet_status');

        if ($status == 'processed') {
            return [
                'label' => trans('exam.schedule.marksheet_statuses.processed'),
                'value' => 'processed',
                'color' => 'bg-success',
            ];
        }

        return [
            'label' => trans('exam.schedule.marksheet_statuses.pending'),
            'value' => 'pending',
            'color' => 'bg-warning',
        ];
    }

    public function getMarksheetAvailableAttribute(): bool
    {
        if (! auth()->user()->hasAnyRole(['student', 'guardian'])) {
            return false;
        }

        if (Arr::get($this->marksheet_status, 'value') != 'processed') {
            return false;
        }

        if (empty($this->getConfig('result_date'))) {
            return false;
        }

        if (Carbon::parse($this->getConfig('result_date'))->isFuture()) {
            return false;
        }

        if (! $this->relationLoaded('exam')) {
            return false;
        }

        if (! Arr::get($this->exam->config, 'first_attempt.publish_marksheet')) {
            return false;
        }

        return true;
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $batchIds = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->get()
            ->pluck('id')
            ->all();

        $query->whereIn('exam_schedules.batch_id', $batchIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('exam.schedule.schedule'));
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('exam', function ($q) use ($periodId) {
            $q->byPeriod($periodId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('exam_schedule')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
