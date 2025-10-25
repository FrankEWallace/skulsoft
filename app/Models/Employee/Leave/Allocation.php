<?php

namespace App\Models\Employee\Leave;

use App\Casts\DateCast;
use App\Concerns\HasDatePeriod;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Allocation extends Model
{
    use HasDatePeriod, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'leave_allocations';

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(AllocationRecord::class, 'leave_allocation_id');
    }

    public function scopeByTeam(Builder $query)
    {
        $query->whereHas('employee', function ($q) {
            $q->whereHas('contact', function ($q) {
                $q->whereTeamId(auth()->user()?->current_team_id);
            });
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->basic()])
            ->getOrFail(trans('employee.leave.allocation.allocation'), $field);
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->detail(), 'records.type'])
            ->getOrFail(trans('employee.leave.allocation.allocation'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave_allocation')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
