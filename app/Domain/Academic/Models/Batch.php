<?php

namespace App\Domain\Academic\Models;

use App\Casts\DateCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Audience;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Batch extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'batches';

    protected $attributes = [];

    protected $casts = [
        'period_start_date' => DateCast::class,
        'period_end_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
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

    public function audiences()
    {
        return $this->morphToMany(Audience::class, 'audienceable');
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
            },
            'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithLastIncharge(Builder $query)
    {
        $query->addSelect([
            'incharge_id' => Incharge::select('id')
                ->whereColumn('model_id', 'batches.id')
                ->where('model_type', 'Batch')
                ->where('effective_date', '<=', today()->toDateString())
                ->orderBy('effective_date', 'desc')
                ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeFilterAccessible(Builder $query, bool $forSubject = true, ?string $date = null)
    {
        if (auth()->user()->is_default) {
            return;
        }

        if (auth()->user()->can('academic:admin-access')) {
            return;
        }

        if (auth()->user()->is_student_or_guardian) {

            $batchIds = Student::query()
                ->byPeriod()
                ->filterForStudentAndGuardian()
                ->pluck('batch_id')
                ->all();

            $query->whereIn('batches.id', $batchIds);

            return;
        }

        if (! auth()->user()->can('academic:incharge-access')) {
            $query->whereNull('batches.id');

            return;
        }

        $date = $date ?? today()->toDateString();

        $employee = Employee::auth()->first();

        $incharges = Incharge::query()
            ->whereIn('model_type', ['AcademicDepartment', 'Program', 'Division', 'Course', 'Batch', 'Subject'])
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->get();

        if ($forSubject) {
            $timetableAllocationBatches = TimetableAllocation::query()
                ->select('timetables.batch_id')
                ->join('timetable_records', 'timetable_allocations.timetable_record_id', '=', 'timetable_records.id')
                ->join('timetables', 'timetable_records.timetable_id', '=', 'timetables.id')
                ->where('timetable_allocations.employee_id', $employee->id)
                ->get();

            $inchargeBatchIds = array_merge(
                $incharges->where('model_type', 'Batch')->pluck('model_id')->all(),
                $incharges->where('model_type', 'Subject')->where('detail_type', 'Batch')->pluck('detail_id')->all(),
                $timetableAllocationBatches->pluck('batch_id')->all()
            );
        } else {
            $inchargeBatchIds = $incharges->where('model_type', 'Batch')->pluck('model_id')->all();
        }

        $batchIds = self::query()
            ->select('batches.id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->join('divisions', 'divisions.id', '=', 'courses.division_id')
            ->join('programs', 'programs.id', '=', 'divisions.program_id')
            ->leftJoin('academic_departments', 'academic_departments.id', '=', 'programs.department_id')
            ->whereIn('academic_departments.id', $incharges->where('model_type', 'AcademicDepartment')->pluck('model_id')->all())
            ->orWhereIn('programs.id', $incharges->where('model_type', 'Program')->pluck('model_id')->all())
            ->orWhereIn('divisions.id', $incharges->where('model_type', 'Division')->pluck('model_id')->all())
            ->orWhereIn('courses.id', $incharges->where('model_type', 'Course')->pluck('model_id')->all())
            ->orWhereIn('batches.id', $inchargeBatchIds)
            ->pluck('id')
            ->all();

        $query->whereIn('batches.id', $batchIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('academic.batch.batch'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('course', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('course', function ($q) use ($periodId) {
            $q->byPeriod($periodId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('batch')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
