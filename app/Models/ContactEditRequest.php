<?php

namespace App\Models;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Concerns\StudentAccess;
use App\Concerns\SubordinateAccess;
use App\Enums\ContactEditStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContactEditRequest extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity, StudentAccess, SubordinateAccess;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'contact_edit_requests';

    protected $casts = [
        'status' => ContactEditStatus::class,
        'processed_at' => DateTimeCast::class,
        'data' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'ContactEditRequest';
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilterAccessibleForStudent(Builder $query)
    {
        $studentIds = $this->getAccessibleStudentIds();

        $query->where('contact_edit_requests.model_type', 'Student')
            ->whereIn('contact_edit_requests.model_id', $studentIds);
    }

    public function scopeFilterAccessibleForEmployee(Builder $query)
    {
        $employeeIds = $this->getAccessibleEmployeeIds();

        $query->where('contact_edit_requests.model_type', 'Employee')
            ->whereIn('contact_edit_requests.model_id', $employeeIds);
    }

    public function scopeFindForStudentByUuidOrFail(Builder $query, string $uuid)
    {
        return $query->whereUuid($uuid)
            ->with(['model' => fn ($q) => $q->basic()])
            ->filterAccessibleForStudent()
            ->getOrFail(trans('student.edit_request.edit_request'));
    }

    public function scopeFindForEmployeeByUuidOrFail(Builder $query, string $uuid)
    {
        return $query->whereUuid($uuid)
            ->with(['model' => fn ($q) => $q->basic()])
            ->filterAccessibleForEmployee()
            ->getOrFail(trans('employee.edit_request.edit_request'));
    }

    public function scopeFindDetailForStudentByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['model' => fn ($q) => $q->detail(), 'user'])
            ->filterAccessibleForStudent()
            ->getOrFail(trans('student.edit_request.edit_request'), $field);
    }

    public function scopeFindDetailForEmployeeByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['model' => fn ($q) => $q->detail(), 'user'])
            ->filterAccessibleForEmployee()
            ->getOrFail(trans('employee.edit_request.edit_request'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contact_edit_request')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
