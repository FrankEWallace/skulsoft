<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Concerns\HasDatePeriod;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Concerns\StudentAccess;
use App\Contracts\Mediable;
use App\Enums\Student\LeaveRequestStatus;
use App\Helpers\CalHelper;
use App\Models\Option;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveRequest extends Model implements Mediable
{
    use HasDatePeriod, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity, StudentAccess;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'leave_requests';

    protected $casts = [
        'status' => LeaveRequestStatus::class,
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'StudentLeaveRequest';
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'category_id');
    }

    public function getPeriodAttribute()
    {
        return CalHelper::getPeriod($this->start_date->value, $this->end_date->value);
    }

    public function getDurationAttribute()
    {
        return CalHelper::getDuration($this->start_date->value, $this->end_date->value, 'day');
    }

    public function getIsEditableAttribute()
    {
        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if ($this->request_user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $studentIds = $this->getAccessibleStudentIds();

        $query->where('leave_requests.model_type', 'Student')
            ->whereIn('leave_requests.model_id', $studentIds);
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('model', function ($q) use ($periodId) {
            $q->where('period_id', $periodId);
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->whereUuid($uuid)
            ->filterAccessible()
            ->with(['model' => fn ($q) => $q->basic()])
            ->getOrFail(trans('student.leave_request.leave_request'));
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->whereUuid($uuid)
            ->filterAccessible()
            ->with(['model' => fn ($q) => $q->detail(), 'category', 'requester'])
            ->getOrFail(trans('student.leave_request.leave_request'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave_request')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
