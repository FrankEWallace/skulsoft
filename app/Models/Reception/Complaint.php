<?php

namespace App\Models\Reception;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Casts\TimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Reception\ComplaintStatus;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Option;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Complaint extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'complaints';

    protected $casts = [
        'date' => DateCast::class,
        'resolved_at' => DateTimeCast::class,
        'time' => TimeCast::class,
        'status' => ComplaintStatus::class,
        'complainant' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Complaint';
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'type_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ComplaintLog::class);
    }

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
    }

    public function getIsEditableAttribute()
    {
        if ($this->getMeta('is_online')) {
            if ($this->user_id != auth()->id()) {
                return false;
            }

            if ($this->date->value != today()->toDateString()) {
                return false;
            }
        }

        if ($this->status != ComplaintStatus::SUBMITTED) {
            return false;
        }

        return true;
    }

    public function scopeWithCurrentIncharges(Builder $query)
    {
        $query->with([
            'incharges', 'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithFirstIncharge(Builder $query)
    {
        $query->addSelect(['incharge_id' => Incharge::select('id')
            ->whereColumn('model_id', 'complaints.id')
            ->where('model_type', 'Complaint')
            ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->hasPermissionTo('complaint:admin-access')) {
            return;
        }

        if (auth()->user()->is_student_or_guardian) {
            $query->whereUserId(auth()->id());
        } else {
            $employee = Employee::query()
                ->auth()
                ->first();

            if ($employee) {
                $query->whereHas('incharges', function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                });
            }
        }
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->byTeam()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('reception.complaint.complaint'), $field);
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->byTeam()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->summary(), 'model' => fn ($q) => $q->summary(), 'type', 'user'])
            ->getOrFail(trans('reception.complaint.complaint'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('complaint')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
