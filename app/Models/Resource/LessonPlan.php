<?php

namespace App\Models\Resource;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Resource\LessonPlanStatus;
use App\Models\Academic\Batch;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LessonPlan extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'lesson_plans';

    protected $attributes = [];

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'status' => LessonPlanStatus::class,
        'is_locked' => 'boolean',
        'details' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'LessonPlan';
    }

    public function records()
    {
        return $this->morphMany(BatchSubjectRecord::class, 'model');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeWithUserId(Builder $query)
    {
        $query
            ->select('lesson_plans.*', 'contacts.user_id')
            ->leftJoin('employees', 'employees.id', '=', 'lesson_plans.employee_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'employees.contact_id');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->hasRole('staff')) {
            $query->whereHas('employee', function ($q) {
                $q->whereHas('contact', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            });

            return;
        }

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

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->with('records')
            ->byPeriod()
            ->withUserId()
            ->filterAccessible()
            ->where('lesson_plans.uuid', $uuid)
            ->getOrFail(trans('resource.lesson_plan.lesson_plan'));
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getIsEditableAttribute(): bool
    {
        if (! auth()->user()->can('lesson-plan:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_lesson_plan_by_accessible_user')) {
            return true;
        }

        if (auth()->user()->hasRole('staff') && $this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function getIsDeletableAttribute(): bool
    {
        if (! auth()->user()->can('lesson-plan:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_lesson_plan_by_accessible_user')) {
            return true;
        }

        if (auth()->user()->hasRole('staff') && $this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('lesson_plan')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
