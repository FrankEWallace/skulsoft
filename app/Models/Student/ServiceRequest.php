<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Concerns\StudentAccess;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Enums\ServiceType;
use App\Models\RequestRecord;
use App\Models\Transport\Stoppage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceRequest extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity, StudentAccess;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'service_requests';

    protected $casts = [
        'date' => DateCast::class,
        'type' => ServiceType::class,
        'request_type' => ServiceRequestType::class,
        'status' => ServiceRequestStatus::class,
        'processed_at' => DateTimeCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'StudentServiceRequest';
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id');
    }

    public function requestRecords(): MorphMany
    {
        return $this->morphMany(RequestRecord::class, 'model');
    }

    public function transportStoppage(): BelongsTo
    {
        return $this->belongsTo(Stoppage::class);
    }

    public function getIsEditableAttribute()
    {
        if ($this->status != ServiceRequestStatus::REQUESTED) {
            return false;
        }

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

        $query->where('service_requests.model_type', 'Student')
            ->whereIn('service_requests.model_id', $studentIds);
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
            ->getOrFail(trans('student.service_request.service_request'));
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->whereUuid($uuid)
            ->filterAccessible()
            ->with(['model' => fn ($q) => $q->detail(), 'requester', 'transportStoppage'])
            ->getOrFail(trans('student.service_request.service_request'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('service_request')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
