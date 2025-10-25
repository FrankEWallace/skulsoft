<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Concerns\StudentAccess;
use App\Enums\Student\TransferRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TransferRequest extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity, StudentAccess;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transfer_requests';

    protected $casts = [
        'request_date' => DateCast::class,
        'processed_at' => DateTimeCast::class,
        'status' => TransferRequestStatus::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'TransferRequest';
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getIsEditableAttribute()
    {
        if ($this->status != TransferRequestStatus::REQUESTED) {
            return false;
        }

        if (auth()->user()->hasRole('admin')) {
            return true;
        }

        if ($this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $studentIds = $this->getAccessibleStudentIds();

        $query->whereIn('transfer_requests.student_id', $studentIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->with(['student' => fn ($q) => $q->summary()])
            ->byPeriod()
            ->filterAccessible()
            ->where('transfer_requests.uuid', $uuid)
            ->getOrFail(trans('student.transfer_request.transfer_request'));
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()?->current_period_id;

        $query->whereHas('student', function ($q) use ($periodId) {
            $q->byPeriod($periodId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transfer_request')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
