<?php

namespace App\Models\Academic;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'academic_departments';

    protected $attributes = [];

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
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
            ->whereColumn('model_id', 'academic_departments.id')
            ->where('model_type', 'AcademicDepartment')
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
            ->whereIn('model_type', ['AcademicDepartment'])
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->get();

        $departmentIds = self::query()
            ->select('academic_departments.id')
            ->whereIn('academic_departments.id', $incharges->where('model_type', 'AcademicDepartment')->pluck('model_id')->all())
            ->pluck('id')
            ->get();

        $query->whereIn('academic_departments.id', $departmentIds);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->where('academic_departments.uuid', $uuid)
            ->getOrFail(trans('academic.department.department'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->where('team_id', $teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('academic_department')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
