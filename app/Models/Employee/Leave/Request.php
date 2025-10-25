<?php

namespace App\Models\Employee\Leave;

use App\Casts\DateCast;
use App\Concerns\HasDatePeriod;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Contracts\Mediable;
use App\Enums\Employee\Leave\RequestStatus;
use App\Helpers\CalHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Request extends Model implements Mediable
{
    use HasDatePeriod, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'leave_requests';

    protected $casts = [
        'status' => RequestStatus::class,
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'LeaveRequest';
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'leave_type_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(RequestRecord::class, 'leave_request_id');
    }

    public function getPeriodAttribute()
    {
        return CalHelper::getPeriod($this->start_date->value, $this->end_date->value);
    }

    public function getDurationAttribute()
    {
        return CalHelper::getDuration($this->start_date->value, $this->end_date->value, 'day');
    }

    public function scopeByTeam(Builder $query)
    {
        $query->whereHas('model', function ($q) {
            $q->whereHas('contact', function ($q) {
                $q->whereTeamId(auth()->user()->current_team_id);
            });
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query->whereUuid($uuid)
            ->with(['model' => fn ($q) => $q->basic()])
            ->getOrFail(trans('employee.leave.request.request'));
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->addSelect([
                'comment' => RequestRecord::select('comment')
                    ->whereColumn('leave_request_id', 'leave_requests.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1),
            ])
            ->with(['model' => fn ($q) => $q->detail(), 'type', 'records'])
            ->getOrFail(trans('employee.leave.request.request'), $field);
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
