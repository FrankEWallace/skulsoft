<?php

namespace App\Domain\Academic\Models;

use App\Casts\DateCast;
use App\Casts\PriceCast;
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

class Course extends Model
{
    use HasConfig, HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'courses';

    protected $attributes = [];

    protected $casts = [
        'period_start_date' => DateCast::class,
        'period_end_date' => DateCast::class,
        'enable_registration' => 'boolean',
        'registration_fee' => PriceCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function subjectRecords(): HasMany
    {
        return $this->hasMany(SubjectRecord::class);
    }

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
    }

    public function enrollmentSeats(): HasMany
    {
        return $this->hasMany(EnrollmentSeat::class);
    }

    public function audiences()
    {
        return $this->morphToMany(Audience::class, 'audienceable');
    }

    public function getNameWithTermAttribute()
    {
        if (empty($this->term)) {
            return $this->name;
        }

        return $this->name.' ('.$this->term.')';
    }

    public function getNameWithTermAndDivisionAttribute()
    {
        if ($this->division_name) {
            return $this->name_with_term.' - '.$this->division_name;
        }

        return $this->name_with_term;
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
            ->whereColumn('model_id', 'courses.id')
            ->where('model_type', 'Course')
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
            ->whereIn('model_type', ['AcademicDepartment', 'Program', 'Division', 'Course'])
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->get();

        $courseIds = self::query()
            ->select('courses.id')
            ->join('divisions', 'divisions.id', '=', 'courses.division_id')
            ->join('programs', 'programs.id', '=', 'divisions.program_id')
            ->leftJoin('academic_departments', 'academic_departments.id', '=', 'programs.department_id')
            ->whereIn('academic_departments.id', $incharges->where('model_type', 'AcademicDepartment')->pluck('model_id')->all())
            ->orWhereIn('programs.id', $incharges->where('model_type', 'Program')->pluck('model_id')->all())
            ->orWhereIn('divisions.id', $incharges->where('model_type', 'Division')->pluck('model_id')->all())
            ->orWhereIn('courses.id', $incharges->where('model_type', 'Course')->pluck('model_id')->all())
            ->pluck('id')
            ->all();

        $query->whereIn('courses.id', $courseIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->getOrFail(trans('academic.course.course'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('division', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('division', function ($q) use ($periodId) {
            $q->wherePeriodId($periodId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('course')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
