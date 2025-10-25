<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Casts\EnumCast;
use App\Casts\PriceCast;
use App\Concerns\HasConfig;
use App\Concerns\HasCustomField;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\CustomFieldForm;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Student\RegistrationStatus;
use App\Models\Academic\Course;
use App\Models\Academic\Period;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Finance\Transaction;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Registration extends Model
{
    use HasConfig, HasCustomField, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'registrations';

    protected $casts = [
        'date' => DateCast::class,
        'rejected_at' => DateTimeCast::class,
        'fee' => PriceCast::class,
        'status' => RegistrationStatus::class,
        'payment_status' => EnumCast::class.':'.PaymentStatus::class,
        'is_online' => 'boolean',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Registration';
    }

    public function customFieldFormName(): string
    {
        return CustomFieldForm::REGISTRATION->value;
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'stage_id');
    }

    public function enrollmentType(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'enrollment_type_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function admission(): HasOne
    {
        return $this->hasOne(Admission::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function scopeDetail(Builder $query)
    {
        return $query
            ->select('registrations.*', 'courses.name as course_name', 'divisions.name as division_name', 'programs.name as program_name', 'periods.name as period_name')
            ->join('courses', 'courses.id', '=', 'registrations.course_id')
            ->join('divisions', 'divisions.id', '=', 'courses.division_id')
            ->join('programs', 'programs.id', '=', 'divisions.program_id')
            ->join('periods', 'periods.id', '=', 'registrations.period_id')
            ->join('contacts', 'contacts.id', '=', 'registrations.contact_id');
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereHas('period', function ($q) {
                $q->team_id = auth()->user()->current_team_id;
            })
            ->whereUuid($uuid)
            ->getOrFail(trans('student.registration.registration'));
    }

    public function getIsConvertedAttribute(): bool
    {
        return (bool) $this->getMeta('is_converted');
    }

    public function isEditable()
    {
        if ($this->status != RegistrationStatus::PENDING) {
            return false;
        }

        // if ($this->fee->value > 0 && $this->payment_status != PaymentStatus::UNPAID) {
        //     return false;
        // }

        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('registration')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
