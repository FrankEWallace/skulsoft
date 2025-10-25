<?php

namespace App\Models\Reception;

use App\Casts\DateCast;
use App\Concerns\HasCustomField;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\CustomFieldForm;
use App\Enums\Reception\EnquiryNature;
use App\Enums\Reception\EnquiryStatus;
use App\Models\Academic\Course;
use App\Models\Academic\Period;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Enquiry extends Model
{
    use HasCustomField, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'enquiries';

    protected $casts = [
        'nature' => EnquiryNature::class,
        'date' => DateCast::class,
        'status' => EnquiryStatus::class,
        'alternate_records' => 'array',
        'meta' => 'array',
    ];

    public function customFieldFormName(): string
    {
        return CustomFieldForm::ENQUIRY->value;
    }

    public function getModelName(): string
    {
        return 'Enquiry';
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'stage_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'type_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'source_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(EnquiryRecord::class, 'enquiry_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(EnquiryFollowUp::class, 'enquiry_id');
    }

    public function getIsConvertedAttribute(): bool
    {
        return (bool) $this->getMeta('is_converted');
    }

    public function getIsEditableAttribute(): bool
    {
        if ($this->is_converted) {
            return false;
        }

        if ($this->status == EnquiryStatus::CLOSE) {
            return false;
        }

        return true;
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->can('enquiry:admin-access')) {
            return $query;
        }

        $employeeId = Employee::query()
            ->auth()
            ->first()?->id;

        if (! $employeeId) {
            return $query->where('employee_id', 0);
        }

        return $query->where('employee_id', $employeeId);
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

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('reception.enquiry.enquiry'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('enquiry')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
