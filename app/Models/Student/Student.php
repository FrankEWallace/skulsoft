<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasTags;
use App\Concerns\HasUuid;
use App\Enums\Student\AttendanceSession;
use App\Models\Academic\Batch;
use App\Models\Academic\Period;
use App\Models\Academic\SubjectRecord;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Finance\FeeStructure;
use App\Models\Finance\Transaction;
use App\Models\GroupMember;
use App\Models\Guardian;
use App\Models\Option;
use App\Models\Tag;
use App\Scopes\Student\StudentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasStorage, HasTags, HasUuid, LogsActivity, StudentScope;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'students';

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'cancelled_at' => DateTimeCast::class,
        'summary' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Student';
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function groups(): MorphMany
    {
        return $this->morphMany(GroupMember::class, 'model');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function enrollmentType(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'enrollment_type_id');
    }

    public function enrollmentStatus(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'enrollment_status_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'mentor_id');
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeConcessionType(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'fee_concession_type_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'student_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function getPhotoUrlAttribute(): string
    {
        $photo = $this->photo;

        $default = '/images/'.($this->gender ?? 'male').'.png';

        return $this->getImageFile(visibility: 'public', path: $photo, default: $default);
    }

    public function getFeeSummary()
    {
        $fee = $this->fees()
            ->selectRaw('SUM(total) as total_fee, SUM(paid) as paid_fee')
            ->first();

        return [
            'total_fee' => \Price::from($fee->total_fee),
            'paid_fee' => \Price::from($fee->paid_fee),
            'balance_fee' => \Price::from($fee->total_fee - $fee->paid_fee),
        ];
    }

    public function getFeeSummaryOnDate(string $date)
    {
        $fee = $this->fees()
            ->selectRaw('SUM(total) as total_fee, SUM(paid) as paid_fee')
            ->join('fee_installments', 'student_fees.fee_installment_id', '=', 'fee_installments.id')
            ->whereDate(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date, CURRENT_DATE)'), '<=', $date)
            ->first();

        return [
            'total_fee' => \Price::from($fee->total_fee),
            'paid_fee' => \Price::from($fee->paid_fee),
            'balance_fee' => \Price::from($fee->total_fee - $fee->paid_fee),
        ];
    }

    public function getFeeConcessionSummary()
    {
        $fees = Fee::query()
            ->with('concession')
            ->whereNotNull('fee_concession_id')
            ->where('student_id', $this->id)
            ->get();

        return $fees->pluck('concession.name')->unique()->implode(', ');
    }

    public function getSubjectSummary()
    {
        $subjects = SubjectRecord::query()
            ->with('subject')
            ->where(function ($q) {
                $q->where('batch_id', $this->batch_id)
                    ->orWhere('course_id', $this->batch->course_id);
            })
            ->where('has_grading', 0)
            ->get();

        $electiveSubjects = SubjectWiseStudent::query()
            ->where('student_id', $this->id)
            ->whereIn('subject_id', $subjects->pluck('subject_id'))
            ->get();

        $subjectSummary = $subjects->filter(function ($subject) use ($electiveSubjects) {
            return ! $subject->is_elective || ($subject->is_elective && $electiveSubjects->contains('subject_id', $subject->subject_id));
        });

        return $subjectSummary->pluck('subject.name')->implode(', ');
    }

    public function getAttendanceSummary()
    {
        $attendances = Attendance::query()
            ->whereBatchId($this->batch_id)
            ->whereSession(AttendanceSession::FIRST)
            ->where(function ($q) {
                $q->whereNull('meta->is_holiday')
                    ->orWhere('meta->is_holiday', false);
            })
            ->get();

        $codes = [];
        foreach ($attendances as $attendance) {
            $values = Arr::get($attendance, 'values', []);
            foreach ($values as $value) {
                foreach (Arr::get($value, 'uuids', []) as $uuid) {
                    if ($uuid === $this->uuid) {
                        $codes[] = Arr::get($value, 'code');
                    }
                }
            }
        }

        $present = collect($codes)->filter(function ($value) {
            return in_array($value, ['P', 'L', 'HD']);
        })->count();

        $workingDays = $attendances->count();

        return [
            'present_days' => $present,
            'absent_days' => $workingDays - $present,
            'working_days' => $workingDays,
        ];
    }

    public function isStudying()
    {
        // if ($this->end_date->value) {
        //     return false;
        // }

        if ($this->cancelled_at->value) {
            return false;
        }

        return true;
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->basic()
            ->filterAccessible()
            ->where('students.uuid', '=', $uuid)
            ->getOrFail(trans('student.student'));
    }

    public function scopeFindSummaryByUuidForGuestOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->summaryForGuest()
            ->where('students.uuid', '=', $uuid)
            ->getOrFail(trans('student.student'));
    }

    public function scopeFindSummaryByUuidOrFail(Builder $query, ?string $uuid = null, bool $forSubject = false)
    {
        return $query
            ->summary()
            ->filterAccessible($forSubject)
            ->where('students.uuid', '=', $uuid)
            ->getOrFail(trans('student.student'));
    }

    public function scopeFindTransferredByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->filterTransferred()
            ->filterAccessible()
            ->where('students.uuid', '=', $uuid)
            ->getOrFail(trans('student.student'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('contact', function ($q) use ($teamId) {
            $q->whereTeamId($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->where('students.period_id', $periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('student')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
