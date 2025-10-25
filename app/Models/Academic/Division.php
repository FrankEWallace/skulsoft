<?php

namespace App\Models\Academic;

use App\Casts\DateCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Audience;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Division extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'divisions';

    protected $attributes = [];

    protected $casts = [
        'period_start_date' => DateCast::class,
        'period_end_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'audienceable');
    }

    public function getnameWithProgramAttribute()
    {
        if ($this->program_name) {
            return $this->name.' - '.$this->program_name;
        }

        return $this->name;
    }

    public function scopeWithCurrentIncharges(Builder $query)
    {
        $query->with([
            'incharges' => function ($q) {
                return $q->where('start_date', '<=', today()->toDateString())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', today()->toDateString());
                    });
            }, 'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithLastIncharge(Builder $query)
    {
        $query->addSelect(['incharge_id' => Incharge::select('id')
            ->whereColumn('model_id', 'divisions.id')
            ->where('model_type', 'Division')
            ->where('effective_date', '<=', today()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeFilterAccessible(Builder $query, ?string $date = null)
    {
        if (auth()->user()->is_default) {
            return;
        }

        if (auth()->user()->can('academic:admin-access')) {
            return;
        }

        if (! auth()->user()->can('academic:incharge-access')) {
            return;
        }

        $date = $date ?? today()->toDateString();

        $employee = Employee::auth()->first();

        $incharges = Incharge::query()
            ->whereIn('model_type', ['AcademicDepartment', 'Program', 'Division'])
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->get();

        $divisionIds = self::query()
            ->select('divisions.id')
            ->join('programs', 'programs.id', '=', 'divisions.program_id')
            ->leftJoin('academic_departments', 'academic_departments.id', '=', 'programs.department_id')
            ->whereIn('academic_departments.id', $incharges->where('model_type', 'AcademicDepartment')->pluck('model_id')->all())
            ->orWhereIn('programs.id', $incharges->where('model_type', 'Program')->pluck('model_id')->all())
            ->orWhereIn('divisions.id', $incharges->where('model_type', 'Division')->pluck('model_id')->all())
            ->pluck('id')
            ->all();

        $query->whereIn('divisions.id', $divisionIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->getOrFail(trans('academic.division.division'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('period', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('division')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
