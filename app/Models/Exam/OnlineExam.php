<?php

namespace App\Models\Exam;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Casts\PercentCast;
use App\Casts\TimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Exam\OnlineExamType;
use App\Models\Academic\Batch;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Academic\Period;
use App\Models\Employee\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OnlineExam extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'online_exams';

    protected $casts = [
        'type' => OnlineExamType::class,
        'date' => DateCast::class,
        'start_time' => TimeCast::class,
        'end_date' => DateCast::class,
        'end_time' => TimeCast::class,
        'result_published_at' => DateTimeCast::class,
        'published_at' => DateTimeCast::class,
        'cancelled_at' => DateTimeCast::class,
        'pass_percentage' => PercentCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'OnlineExam';
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function records()
    {
        return $this->morphMany(BatchSubjectRecord::class, 'model');
    }

    public function submissions()
    {
        return $this->hasMany(OnlineExamSubmission::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OnlineExamQuestion::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDurationAttribute(): string
    {
        $startDateTime = Carbon::parse($this->date->value.' '.$this->start_time->value);
        $endDate = $this->end_date->value ?: $this->date->value;
        $endDateTime = Carbon::parse($endDate.' '.$this->end_time->value);

        return abs($endDateTime->diffInMinutes($startDateTime)).' '.trans('list.durations.minutes');
    }

    public function getIsLiveAttribute(): bool
    {
        $startDateTime = Carbon::parse($this->date->value.' '.$this->start_time->value);
        $endDate = $this->end_date->value ?: $this->date->value;
        $endDateTime = Carbon::parse($endDate.' '.$this->end_time->value);

        $isLive = false;
        if (now()->between($startDateTime, $endDateTime)) {
            $isLive = true;
        }

        return $isLive;
    }

    public function getUpcomingTimeAttribute(): int
    {
        $startDateTime = Carbon::parse($this->date->value.' '.$this->start_time->value);

        $diffInMinutes = round(now()->diffInMinutes($startDateTime));

        if ($diffInMinutes < 0) {
            return 0;
        }

        return $diffInMinutes;
    }

    public function getIsCompletedAttribute(): bool
    {
        $endDate = $this->end_date->value ?: $this->date->value;
        $endDateTime = Carbon::parse($endDate.' '.$this->end_time->value);

        if (now()->lessThan($endDateTime)) {
            return false;
        }

        return $this->upcoming_time <= 0 && ! $this->is_live;
    }

    public function getCanManageQuestionAttribute(): bool
    {
        if ($this->published_at->value) {
            return false;
        }

        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if (auth()->id() == $this->user_id) {
            return true;
        }

        return false;
    }

    public function getCanUpdateStatusAttribute(): bool
    {
        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if (! auth()->user()->can('online-exam:edit')) {
            return true;
        }

        if (auth()->id() == $this->user_id) {
            return true;
        }

        return false;
    }

    public function getCanEvaluateAttribute(): bool
    {
        if (! $this->published_at->value) {
            return false;
        }

        if ($this->result_published_at->value) {
            return false;
        }

        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if (auth()->id() == $this->user_id) {
            return true;
        }

        return false;
    }

    public function getIsEditableAttribute(): bool
    {
        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if ($this->published_at->value) {
            return false;
        }

        if (! auth()->user()->can('online-exam:edit')) {
            return true;
        }

        if (auth()->id() == $this->user_id) {
            return true;
        }

        return false;
    }

    public function getIsDeletableAttribute(): bool
    {
        if ($this->published_at->value) {
            return false;
        }

        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if (! auth()->user()->can('online-exam:delete')) {
            return true;
        }

        if (auth()->id() == $this->user_id) {
            return true;
        }

        return false;
    }

    public function scopeWithUserId(Builder $query)
    {
        $query
            ->select('online_exams.*', 'contacts.user_id')
            ->leftJoin('employees', 'employees.id', '=', 'online_exams.employee_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'employees.contact_id');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $batchIds = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->get()
            ->pluck('id')
            ->all();

        $query->whereHas('records', function ($q) use ($batchIds) {
            $q->whereIn('batch_subject_records.batch_id', $batchIds);
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->with('records')
            ->byPeriod()
            ->withUserId()
            ->filterAccessible()
            ->where('online_exams.uuid', $uuid)
            ->getOrFail(trans('exam.online_exam.online_exam'));
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('online_exam')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
