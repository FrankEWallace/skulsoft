<?php

namespace App\Models\Resource;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Resource\OnlineClassPlatform;
use App\Models\Academic\Batch;
use App\Models\Academic\BatchSubjectRecord;
use App\Models\Employee\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OnlineClass extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'online_classes';

    protected $attributes = [];

    protected $casts = [
        'start_at' => DateTimeCast::class,
        'platform' => OnlineClassPlatform::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'OnlineClass';
    }

    public function records()
    {
        return $this->morphMany(BatchSubjectRecord::class, 'model');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getEndAtAttribute()
    {
        $startAt = Carbon::parse($this->start_at->value);

        $duration = (int) $this->duration;

        $endAt = $startAt->addMinutes($duration)->toDateTimeString();

        return \Cal::dateTime($endAt);
    }

    public function getShowUrlAttribute()
    {
        if (! auth()->check()) {
            return false;
        }

        if (! auth()->user()->hasAnyRole(['student', 'guardian'])) {
            return true;
        }

        $joiningPeriod = (int) config('config.resource.online_class_joining_period', 10);

        $startAt = Carbon::parse($this->start_at->value);

        $duration = (int) $this->duration;

        if ($startAt->subMinutes($joiningPeriod) > now()) {
            return false;
        }

        if ($this->status == 'ended') {
            return false;
        }

        // modifying startAt again is creating issues
        // $endAt = $startAt->addMinutes($duration);

        // if ($endAt < now()) {
        //     return false;
        // }

        return true;
    }

    public function getMeetingUrlAttribute()
    {
        if ($this->url) {
            return $this->url;
        }

        $meetingCode = $this->meeting_code;

        $platformUrl = $this->platform->url();

        return $platformUrl.$meetingCode;
    }

    public function getStatusAttribute()
    {
        $startAt = Carbon::parse($this->start_at->value);

        $duration = (int) $this->duration;

        if ($startAt > now()) {
            return 'pending';
        }

        $endAt = $startAt->addMinutes($duration);

        if ($endAt < now()) {
            return 'ended';
        }

        return 'live';
    }

    public function scopeWithUserId(Builder $query)
    {
        $query
            ->select('online_classes.*', 'contacts.user_id')
            ->leftJoin('employees', 'employees.id', '=', 'online_classes.employee_id')
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
            ->where('online_classes.uuid', $uuid)
            ->getOrFail(trans('resource.online_class.online_class'));
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getIsEditableAttribute(): bool
    {
        if (! auth()->user()->can('online-class:edit')) {
            return false;
        }

        if (config('config.resource.allow_edit_online_class_by_accessible_user')) {
            return true;
        }

        if (auth()->user()->hasRole('staff') && $this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function getIsDeletableAttribute(): bool
    {
        if (! auth()->user()->can('online-class:delete')) {
            return false;
        }

        if (config('config.resource.allow_delete_online_class_by_accessible_user')) {
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
            ->useLogName('online_class')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
