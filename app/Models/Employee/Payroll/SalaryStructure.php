<?php

namespace App\Models\Employee\Payroll;

use App\Casts\DateCast;
use App\Casts\PriceCast;
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

class SalaryStructure extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'salary_structures';

    protected $casts = [
        'effective_date' => DateCast::class,
        'hourly_pay' => PriceCast::class,
        'net_earning' => PriceCast::class,
        'net_deduction' => PriceCast::class,
        'net_employee_contribution' => PriceCast::class,
        'net_employer_contribution' => PriceCast::class,
        'net_salary' => PriceCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SalaryTemplate::class, 'salary_template_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(SalaryStructureRecord::class, 'salary_structure_id');
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->basic()])
            ->getOrFail(trans('employee.payroll.salary_structure.salary_structure'), $field);
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->detail(), 'records'])
            ->getOrFail(trans('employee.payroll.salary_structure.salary_structure'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('salary_structure')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
