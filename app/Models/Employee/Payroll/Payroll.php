<?php

namespace App\Models\Employee\Payroll;

use App\Casts\DateCast;
use App\Casts\PriceCast;
use App\Concerns\HasDatePeriod;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Enums\Finance\PaymentStatus;
use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\Type as AttendanceType;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payroll extends Model
{
    use HasDatePeriod, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'payrolls';

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'total' => PriceCast::class,
        'paid' => PriceCast::class,
        'status' => PayrollStatus::class,
        'payment_status' => PaymentStatus::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(Record::class, 'payroll_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    public function getPeriodAttribute()
    {
        return CalHelper::getPeriod($this->start_date->value, $this->end_date->value);
    }

    public function getDurationAttribute()
    {
        return CalHelper::getDuration($this->start_date->value, $this->end_date->value, 'day');
    }

    public function getAttendanceSummary()
    {
        $attendanceTypes = AttendanceType::byTeam()->get();

        $attendances = [];
        $payrollAttendances = $this->getMeta('attendances') ?? [];
        foreach ($payrollAttendances as $attendance) {
            if (Arr::get($attendance, 'code') == 'L') {
                $attendances[] = Arr::add($attendance, 'name', trans('employee.leave.leave'));
            } elseif (Arr::get($attendance, 'code') == 'WH') {
                $attendances[] = Arr::add($attendance, 'name', trans('employee.payroll.salary_structure.working_hours'));
            }

            $attendanceType = $attendanceTypes->firstWhere('code', Arr::get($attendance, 'code'));

            if ($attendanceType) {
                $attendances[] = Arr::add($attendance, 'name', $attendanceType->name);
            }
        }

        return $attendances;
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->basic()])
            ->getOrFail(trans('employee.payroll.payroll'), $field);
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query->whereUuid($uuid)
            ->with(['employee' => fn ($q) => $q->detail(), 'records', 'records.payHead'])
            ->getOrFail(trans('employee.payroll.payroll'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
