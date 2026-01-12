<?php

namespace App\Scopes\Student;

use App\Domain\Academic\Models\Batch;
use App\Support\AsStudentOrGuardian;
use Illuminate\Database\Eloquent\Builder;

trait StudentScope
{
    use AsStudentOrGuardian;

    // For internal operation
    public function scopeBasic(Builder $query)
    {
        $query
            ->select('students.id', 'students.uuid', 'students.batch_id', 'students.period_id', 'students.contact_id', 'students.fee_structure_id', 'students.start_date', 'students.end_date', 'students.cancelled_at', 'contacts.team_id', 'contacts.user_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
    }

    public function scopeAuth(Builder $query, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        $query->select('students.id', 'students.uuid', 'contacts.user_id')
            ->join('contacts', function ($join) use ($userId) {
                $join->on('students.contact_id', '=', 'contacts.id')
                    ->where('contacts.team_id', auth()->user()?->current_team_id)
                    ->where('contacts.user_id', $userId);
            });
    }

    public function scopeRecord(Builder $query)
    {
        $query->select('students.id', 'students.uuid', 'students.batch_id', 'students.period_id',
            'students.contact_id', 'contacts.team_id', 'contacts.user_id', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'courses.id as course_id', 'divisions.id as division_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('divisions', 'courses.division_id', '=', 'divisions.id')
            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
    }

    // To show summary of student for guest
    public function scopeSummaryForGuest(Builder $query)
    {
        $query
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.is_provisional', 'admissions.code_number', 'admissions.provisional_code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'courses.term as course_term', 'contacts.team_id', 'contacts.user_id', 'contacts.gender', 'contacts.photo', 'contacts.birth_date', 'contacts.father_name', 'contacts.mother_name', 'contacts.contact_number')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id');
    }

    // To show summary of student
    public function scopeSummary(Builder $query, ?int $teamId = null)
    {
        $teamId ??= auth()->user()?->current_team_id;

        $query
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", contacts.first_name, contacts.middle_name, contacts.third_name, contacts.last_name), "[[:space:]]+", " ") as name'), 'contacts.email', 'admissions.is_provisional', 'admissions.code_number', 'admissions.provisional_code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'courses.term as course_term', 'contacts.team_id', 'contacts.user_id', 'contacts.gender', 'contacts.photo', 'contacts.birth_date', 'contacts.father_name', 'contacts.mother_name', 'contacts.contact_number', 'enrollment_types.name as enrollment_type_name', 'enrollment_statuses.name as enrollment_status_name')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->leftJoin('options as enrollment_types', 'students.enrollment_type_id', 'enrollment_types.id')
            ->leftJoin('options as enrollment_statuses', 'students.enrollment_status_id', 'enrollment_statuses.id')
            ->where('contacts.team_id', '=', $teamId);
    }

    // To show summary of student without select
    public function scopeSummaryWithoutSelect(Builder $query)
    {
        $query
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
    }

    // To show detail of student
    public function scopeDetail(Builder $query)
    {
        $query
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'contacts.team_id', 'contacts.user_id', 'contacts.first_name', 'contacts.last_name', 'contacts.contact_number', 'contacts.photo', 'contacts.father_name', 'contacts.mother_name', 'contacts.email', 'contacts.birth_date', 'contacts.gender', 'contacts.blood_group', 'contacts.unique_id_number1', 'contacts.unique_id_number2', 'contacts.unique_id_number3', 'contacts.unique_id_number4', 'contacts.unique_id_number5', 'contacts.address', 'contacts.locality', 'guardians.id as guardian_id', 'guardians.contact_id as guardian_contact_id', 'admissions.is_provisional', 'admissions.code_number', 'admissions.provisional_code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'courses.term as course_term', 'religions.uuid as religion_uuid', 'religions.name as religion_name', 'castes.uuid as caste_uuid', 'castes.name as caste_name', 'categories.uuid as category_uuid', 'categories.name as category_name', 'enrollment_types.uuid as enrollment_type_uuid', 'enrollment_types.name as enrollment_type_name', 'enrollment_statuses.uuid as enrollment_status_uuid', 'enrollment_statuses.name as enrollment_status_name', 'users.uuid as user_uuid', 'registrations.uuid as registration_uuid', 'registrations.code_number as registration_code_number')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->leftJoin('users', 'contacts.user_id', '=', 'users.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->leftJoin('registrations', 'admissions.registration_id', '=', 'registrations.id')
            ->leftJoin('guardians', function ($join) {
                $join->on('primary_contact_id', 'contacts.id')->where('guardians.position', '=', 1);
            })
            ->leftJoin('options as religions', 'contacts.religion_id', 'religions.id')
            ->leftJoin('options as castes', 'contacts.caste_id', 'castes.id')
            ->leftJoin('options as categories', 'contacts.category_id', 'categories.id')
            ->leftJoin('options as enrollment_types', 'students.enrollment_type_id', 'enrollment_types.id')
            ->leftJoin('options as enrollment_statuses', 'students.enrollment_status_id', 'enrollment_statuses.id')
            ->where('contacts.team_id', '=', auth()->user()?->current_team_id)
            ->with(['guardian:id,contact_id,primary_contact_id,relation', 'guardian.contact:id,first_name,middle_name,third_name,last_name,contact_number']);
    }

    public function scopeFilterTransferred(Builder $query)
    {
        $query
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'admissions.leaving_remarks', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'courses.term as course_term', 'contacts.gender', 'contacts.photo', 'contacts.birth_date', 'contacts.father_name', 'contacts.mother_name', 'contacts.contact_number', 'options.name as reason', 'options.uuid as reason_uuid')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->join('options', 'admissions.transfer_reason_id', '=', 'options.id')
            ->where('contacts.team_id', '=', auth()->user()?->current_team_id)
            ->where('students.period_id', '=', auth()->user()?->current_period_id)
            ->whereNull('students.cancelled_at')
            ->where('admissions.leaving_date', '!=', null);
    }

    public function scopeFilterStudying(Builder $query)
    {
        $query->whereNull('students.cancelled_at')->where(function ($q) {
            $q->whereNull('admissions.leaving_date')
                ->orWhere('admissions.leaving_date', '>', today()->toDateString());
        });
    }

    public function scopeFilterByStatus(Builder $query, ?string $status = null)
    {
        if ($status == 'all' || empty($status)) {
            return;
        }

        $query->when($status == 'studying', function ($q) {
            $q->filterStudying();
        })
            ->when($status == 'cancelled', function ($q) {
                $q->whereNotNull('students.cancelled_at');
            })
            ->when($status == 'transferred', function ($q) {
                $q->where(function ($q) {
                    $q->whereNotNull('admissions.leaving_date')
                        ->where('admissions.leaving_date', '<=', today()->toDateString());
                });
            })
            ->when($status == 'alumni', function ($q) {
                $q->where('students.meta->is_alumni', true);
            });
    }

    public function scopeFilterForStudentAndGuardian(Builder $query)
    {
        $studentContactIds = $this->getStudentContactIds();

        $query
            ->whereIn('students.contact_id', $studentContactIds);
    }

    public function scopeFilterAccessible(Builder $query, bool $forSubject = false, ?string $date = null)
    {
        if (auth()->user()->is_default) {
            return;
        }

        if (auth()->user()->can('student:admin-access')) {
            return;
        }

        if (auth()->user()->can('student:summary')) {
            return;
        }

        $date ??= today()->toDateString();

        if (auth()->user()->can('student:self-access')) {
            $studentIds = self::query()
                // ->byPeriod() // removing it as students and guardians are unable to access outside of period
                ->filterForStudentAndGuardian()
                ->pluck('students.id')
                ->all();

            $query->whereIn('students.id', $studentIds);
        } elseif (auth()->user()->can('student:incharge-access')) {

            $batchIds = Batch::query()
                ->select('batches.id')
                ->filterAccessible($forSubject)
                ->get()
                ->pluck('id')
                ->all();

            $query->whereIn('students.batch_id', $batchIds);
        }
    }

    public function scopeFilterByAddress(Builder $query, string $address)
    {
        $query->whereHas('contact', function ($q) use ($address) {
            $q->where(
                \DB::raw("LOWER(
                    TRIM(CONCAT_WS(', ',
                        NULLIF(JSON_UNQUOTE(address->'$.present.address_line1'), ''),
                        NULLIF(JSON_UNQUOTE(address->'$.present.address_line2'), ''),
                        NULLIF(JSON_UNQUOTE(address->'$.present.city'), ''),
                        NULLIF(JSON_UNQUOTE(address->'$.present.state'), ''),
                        NULLIF(JSON_UNQUOTE(address->'$.present.country'), ''),
                        NULLIF(CONCAT('', JSON_UNQUOTE(address->'$.present.zipcode')), '')
                    ))
                )"), 'like', '%'.strtolower($address).'%');
        });
    }
}
