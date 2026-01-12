<?php

namespace App\Services;

use App\Enums\OptionType;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\ClassTiming;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Department;
use App\Domain\Academic\Models\Division;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Program;
use App\Domain\Academic\Models\ProgramType;
use App\Domain\Academic\Models\Session;
use App\Domain\Academic\Models\Subject;
use App\Models\Approval\Type as ApprovalType;
use App\Models\Config\Config;
use App\Models\Employee\Department as EmployeeDepartment;
use App\Models\Employee\Designation;
use App\Models\Employee\Employee;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeHead;
use App\Models\Finance\PaymentMethod;
use App\Models\Option;
use App\Models\Team;
use App\Models\Transport\Circle;
use App\Models\Transport\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamImportService
{
    public function import(Request $request, Team $team): void
    {
        $module = $request->module;

        if ($module == 'academic') {
            $this->importAcademicModule($request, $team);
        }

        if ($module == 'student') {
            $this->importStudentModule($request, $team);
        }

        if ($module == 'employee') {
            $this->importEmployeeModule($request, $team);
        }

        if ($module == 'finance') {
            $this->importFinanceModule($request, $team);
        }

        if ($module == 'contact') {
            $this->importContactModule($request, $team);
        }

        if ($module == 'transport') {
            $this->importTransportModule($request, $team);
        }

        if ($module == 'approval') {
            $this->importApprovalModule($request, $team);
        }
    }

    private function getCurrentPeriod(Team $team): Period
    {
        $currentPeriods = Period::query()
            ->where('team_id', $team->id)
            ->get();

        if (! $currentPeriods) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.period.period')])]);
        }

        if ($currentPeriods->count() > 1) {
            throw ValidationException::withMessages(['message' => trans('global.multiple_records', ['attribute' => trans('academic.period.period')])]);
        }

        return $currentPeriods->first();
    }

    public function importAcademicModule(Request $request, Team $team): void
    {
        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'academic')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'academic')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'academic',
                'value' => $config->value,
            ]);
        }

        $subjectTypes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::SUBJECT_TYPE)
            ->get();

        foreach ($subjectTypes as $subjectType) {
            $newSubjectType = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::SUBJECT_TYPE,
                'name' => $subjectType->name,
            ]);

            $newSubjectType->description = $subjectType->description;
            $newSubjectType->meta = $subjectType->meta;
            $newSubjectType->save();
        }

        $departmentExists = Department::query()
            ->where('team_id', $team->id)
            ->exists();

        if ($departmentExists) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.department.department')])]);
        }

        $departments = Department::query()
            ->where('team_id', $request->team_id)
            ->get();

        \DB::beginTransaction();
        foreach ($departments as $department) {
            $newDepartment = $department->replicate();
            $newDepartment->uuid = (string) Str::uuid();
            $newDepartment->team_id = $team->id;
            $newDepartment->save();
        }
        \DB::commit();

        $programTypeExists = ProgramType::query()
            ->where('team_id', $team->id)
            ->exists();

        if ($programTypeExists) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.program_type.program_type')])]);
        }

        $programTypes = ProgramType::query()
            ->where('team_id', $request->team_id)
            ->get();

        \DB::beginTransaction();
        foreach ($programTypes as $programType) {
            $newProgramType = $programType->replicate();
            $newProgramType->uuid = (string) Str::uuid();
            $newProgramType->team_id = $team->id;
            $newProgramType->save();
        }
        \DB::commit();

        $newProgramTypes = ProgramType::query()
            ->where('team_id', $team->id)
            ->get();

        $programExists = Program::query()
            ->where('team_id', $team->id)
            ->exists();

        if ($programExists) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.program.program')])]);
        }

        $programs = Program::query()
            ->with('type')
            ->where('team_id', $request->team_id)
            ->get();

        \DB::beginTransaction();
        foreach ($programs as $program) {
            $newProgramType = null;
            if ($program->type_id) {
                $newProgramType = $newProgramTypes->firstWhere('name', $program->type->name);

                if (! $newProgramType) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.program_type.program_type')])]);
                }
            }

            $newProgram = $program->replicate();
            $newProgram->uuid = (string) Str::uuid();
            $newProgram->team_id = $team->id;
            $newProgram->type_id = $newProgramType?->id;
            $newProgram->save();
        }
        \DB::commit();

        $sessionExists = Session::query()
            ->where('team_id', $team->id)
            ->exists();

        if ($sessionExists) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.session.session')])]);
        }

        $sessions = Session::query()
            ->where('team_id', $request->team_id)
            ->get();

        \DB::beginTransaction();
        foreach ($sessions as $session) {
            $newSession = $session->replicate();
            $newSession->uuid = (string) Str::uuid();
            $newSession->team_id = $team->id;
            $newSession->save();
        }
        \DB::commit();

        $newSessions = Session::query()
            ->where('team_id', $team->id)
            ->get();

        $periodExists = Period::query()
            ->where('team_id', $team->id)
            ->exists();

        if ($periodExists) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.period.period')])]);
        }

        $periods = Period::query()
            ->with('session')
            ->where('team_id', $request->team_id)
            ->get();

        \DB::beginTransaction();
        foreach ($periods as $period) {
            $newSession = null;
            if ($period->session_id) {
                $newSession = $newSessions->firstWhere('name', $period->session->name);

                if (! $newSession) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.session.session')])]);
                }
            }

            $newPeriod = $period->replicate();
            $newPeriod->uuid = (string) Str::uuid();
            $newPeriod->team_id = $team->id;
            $newPeriod->session_id = $newSession?->id;
            $newPeriod->save();
        }
        \DB::commit();

        $currentPeriod = $this->getCurrentPeriod($team);

        $newPrograms = Program::query()
            ->where('team_id', $team->id)
            ->get();

        $divisions = Division::query()
            ->with('program')
            ->where('period_id', $request->period_id)
            ->get();

        $existingDivisions = Division::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        if ($existingDivisions->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.division.division')])]);
        }

        \DB::beginTransaction();
        foreach ($divisions as $division) {
            $newProgram = null;
            if ($division->program_id) {
                $newProgram = $newPrograms->firstWhere('name', $division->program->name);

                if (! $newProgram) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.program.program')])]);
                }
            }

            $newDivision = $division->replicate();
            $newDivision->uuid = (string) Str::uuid();
            $newDivision->period_id = $currentPeriod->id;
            $newDivision->program_id = $newProgram?->id;
            $newDivision->save();
        }
        \DB::commit();

        $newDivisions = Division::query()
            ->whereIn('program_id', $newPrograms->pluck('id'))
            ->get();

        $courses = Course::query()
            ->whereIn('division_id', $divisions->pluck('id'))
            ->get();

        $existingCourses = Course::query()
            ->whereIn('division_id', $newDivisions->pluck('id'))
            ->get();

        if ($existingCourses->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.course.course')])]);
        }

        \DB::beginTransaction();
        foreach ($courses as $course) {
            $newDivision = $newDivisions->firstWhere('name', $course->division->name);

            if (! $newDivision) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.division.division')])]);
            }

            $newCourse = $course->replicate();
            $newCourse->uuid = (string) Str::uuid();
            $newCourse->division_id = $newDivision?->id;
            $newCourse->save();
        }
        \DB::commit();

        $newCourses = Course::query()
            ->whereIn('division_id', $newDivisions->pluck('id'))
            ->get();

        $batches = Batch::query()
            ->whereIn('course_id', $courses->pluck('id'))
            ->get();

        $existingBatches = Batch::query()
            ->whereIn('course_id', $newCourses->pluck('id'))
            ->get();

        if ($existingBatches->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.batch.batch')])]);
        }

        \DB::beginTransaction();
        foreach ($batches as $batch) {
            $newCourse = $newCourses->firstWhere('name', $batch->course->name);

            if (! $newCourse) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.course.course')])]);
            }

            $newBatch = $batch->replicate();
            $newBatch->uuid = (string) Str::uuid();
            $newBatch->course_id = $newCourse?->id;
            $newBatch->save();
        }
        \DB::commit();

        $subjectTypes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::SUBJECT_TYPE)
            ->get();

        $subjects = Subject::query()
            ->with('type')
            ->where('period_id', $request->period_id)
            ->get();

        $existingSubjects = Subject::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        if ($existingSubjects->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.subject.subject')])]);
        }

        \DB::beginTransaction();
        foreach ($subjects as $subject) {
            $newSubjectType = null;
            if ($subject->type_id) {
                $newSubjectType = $subjectTypes->firstWhere('name', $subject->type->name);

                if (! $newSubjectType) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.subject.type.type')])]);
                }
            }

            $newSubject = $subject->replicate();
            $newSubject->uuid = (string) Str::uuid();
            $newSubject->period_id = $currentPeriod->id;
            $newSubject->type_id = $newSubjectType?->id;
            $newSubject->save();
        }
        \DB::commit();

        $classTimings = ClassTiming::query()
            ->with('sessions')
            ->where('period_id', $request->period_id)
            ->get();

        $existingClassTimings = ClassTiming::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        if ($existingClassTimings->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('academic.class_timing.class_timing')])]);
        }

        \DB::beginTransaction();
        foreach ($classTimings as $classTiming) {
            $newClassTiming = $classTiming->replicate();
            $newClassTiming->uuid = (string) Str::uuid();
            $newClassTiming->period_id = $currentPeriod->id;
            unset($newClassTiming->sessions);
            $newClassTiming->save();

            foreach ($classTiming->sessions as $session) {
                $newSession = $session->replicate();
                $newSession->uuid = (string) Str::uuid();
                $newSession->class_timing_id = $newClassTiming->id;
                $newSession->save();
            }
        }
        \DB::commit();
    }

    public function importStudentModule(Request $request, Team $team): void
    {
        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'student')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'student')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'student',
                'value' => $config->value,
            ]);
        }

        $transferReasons = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::STUDENT_TRANSFER_REASON)
            ->get();

        foreach ($transferReasons as $transferReason) {
            $newTransferReason = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::STUDENT_TRANSFER_REASON,
                'name' => $transferReason->name,
            ]);

            $newTransferReason->description = $transferReason->description;
            $newTransferReason->meta = $transferReason->meta;
            $newTransferReason->save();
        }

        $enrollmentTypes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE)
            ->get();

        foreach ($enrollmentTypes as $enrollmentType) {
            $newEnrollmentType = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::STUDENT_ENROLLMENT_TYPE,
                'name' => $enrollmentType->name,
            ]);

            $newEnrollmentType->description = $enrollmentType->description;
            $newEnrollmentType->meta = $enrollmentType->meta;
            $newEnrollmentType->save();
        }

        $documentTypes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::STUDENT_DOCUMENT_TYPE)
            ->get();

        foreach ($documentTypes as $documentType) {
            $newDocumentType = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::STUDENT_DOCUMENT_TYPE,
                'name' => $documentType->name,
            ]);

            $newDocumentType->description = $documentType->description;
            $newDocumentType->meta = $documentType->meta;
            $newDocumentType->save();
        }

        $leaveCategories = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::STUDENT_LEAVE_CATEGORY)
            ->get();

        foreach ($leaveCategories as $leaveCategory) {
            $newLeaveCategory = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::STUDENT_LEAVE_CATEGORY,
                'name' => $leaveCategory->name,
            ]);

            $newLeaveCategory->description = $leaveCategory->description;
            $newLeaveCategory->meta = $leaveCategory->meta;
            $newLeaveCategory->save();
        }

        $studentGroups = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::STUDENT_GROUP)
            ->get();

        foreach ($studentGroups as $studentGroup) {
            $newStudentGroup = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::STUDENT_GROUP,
                'name' => $studentGroup->name,
            ]);

            $newStudentGroup->description = $studentGroup->description;
            $newStudentGroup->meta = $studentGroup->meta;
            $newStudentGroup->save();
        }
    }

    public function importEmployeeModule(Request $request, Team $team): void
    {
        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'employee')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'employee')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'employee',
                'value' => $config->value,
            ]);
        }

        $employmentStatuses = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::EMPLOYMENT_STATUS)
            ->get();

        foreach ($employmentStatuses as $employmentStatus) {
            $newEmploymentStatus = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::EMPLOYMENT_STATUS,
                'name' => $employmentStatus->name,
            ]);

            $newEmploymentStatus->description = $employmentStatus->description;
            $newEmploymentStatus->meta = $employmentStatus->meta;
            $newEmploymentStatus->save();
        }

        $employmentTypes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::EMPLOYMENT_TYPE)
            ->get();

        foreach ($employmentTypes as $employmentType) {
            $newEmploymentType = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::EMPLOYMENT_TYPE,
                'name' => $employmentType->name,
            ]);

            $newEmploymentType->description = $employmentType->description;
            $newEmploymentType->meta = $employmentType->meta;
            $newEmploymentType->save();
        }

        $documentTypes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::EMPLOYEE_DOCUMENT_TYPE)
            ->get();

        foreach ($documentTypes as $documentType) {
            $newDocumentType = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::EMPLOYEE_DOCUMENT_TYPE,
                'name' => $documentType->name,
            ]);

            $newDocumentType->description = $documentType->description;
            $newDocumentType->meta = $documentType->meta;
            $newDocumentType->save();
        }

        $qualificationLevels = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::QUALIFICATION_LEVEL)
            ->get();

        foreach ($qualificationLevels as $qualificationLevel) {
            $newQualificationLevel = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::QUALIFICATION_LEVEL,
                'name' => $qualificationLevel->name,
            ]);

            $newQualificationLevel->description = $qualificationLevel->description;
            $newQualificationLevel->meta = $qualificationLevel->meta;
            $newQualificationLevel->save();
        }

        $employeeGroups = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::EMPLOYEE_GROUP)
            ->get();

        foreach ($employeeGroups as $employeeGroup) {
            $newEmployeeGroup = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::EMPLOYEE_GROUP,
                'name' => $employeeGroup->name,
            ]);

            $newEmployeeGroup->description = $employeeGroup->description;
            $newEmployeeGroup->meta = $employeeGroup->meta;
            $newEmployeeGroup->save();
        }

        $departments = EmployeeDepartment::query()
            ->where('team_id', $request->team_id)
            ->get();

        $existingDepartments = EmployeeDepartment::query()
            ->where('team_id', $team->id)
            ->get();

        if ($existingDepartments->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('employee.department.department')])]);
        }

        \DB::beginTransaction();
        foreach ($departments as $department) {
            $newDepartment = $department->replicate();
            $newDepartment->uuid = (string) Str::uuid();
            $newDepartment->team_id = $team->id;
            $newDepartment->save();
        }
        \DB::commit();

        $designations = Designation::query()
            ->with('parent')
            ->where('team_id', $request->team_id)
            ->orderBy('id', 'asc')
            ->get();

        $existingDesignations = Designation::query()
            ->where('team_id', $team->id)
            ->get();

        if ($existingDesignations->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('employee.designation.designation')])]);
        }

        \DB::beginTransaction();
        $newDesignations = collect([]);
        foreach ($designations as $designation) {
            $newParentDesignation = null;
            if ($designation->parent_id) {
                $parentDesignation = $designations->firstWhere('id', $designation->parent_id);

                if (! $parentDesignation) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('employee.designation.designation')])]);
                }

                $newParentDesignation = $newDesignations->firstWhere('name', $parentDesignation->name);

                if (! $newParentDesignation) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('employee.designation.designation')])]);
                }
            }

            $newDesignation = $designation->replicate();
            $newDesignation->uuid = (string) Str::uuid();
            $newDesignation->team_id = $team->id;
            $newDesignation->parent_id = $newParentDesignation?->id;
            $newDesignation->save();

            $newDesignations->push($newDesignation);
        }
        \DB::commit();
    }

    public function importFinanceModule(Request $request, Team $team): void
    {
        $currentPeriod = $this->getCurrentPeriod($team);

        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'finance')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'finance')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'finance',
                'value' => $config->value,
            ]);
        }

        $paymentMethods = PaymentMethod::query()
            ->where('team_id', $request->team_id)
            ->get();

        $existingPaymentMethods = PaymentMethod::query()
            ->where('team_id', $team->id)
            ->get();

        if ($existingPaymentMethods->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('finance.payment_method.payment_method')])]);
        }

        \DB::beginTransaction();
        foreach ($paymentMethods as $paymentMethod) {
            $newPaymentMethod = $paymentMethod->replicate();
            $newPaymentMethod->uuid = (string) Str::uuid();
            $newPaymentMethod->team_id = $team->id;
            $newPaymentMethod->save();
        }
        \DB::commit();

        $feeGroups = FeeGroup::query()
            ->where('period_id', $request->period_id)
            ->get();

        $existingFeeGroups = FeeGroup::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        if ($existingFeeGroups->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('finance.fee_group.fee_group')])]);
        }

        \DB::beginTransaction();
        foreach ($feeGroups as $feeGroup) {
            $newFeeGroup = $feeGroup->replicate();
            $newFeeGroup->uuid = (string) Str::uuid();
            $newFeeGroup->period_id = $currentPeriod->id;
            $newFeeGroup->save();
        }
        \DB::commit();

        $newFeeGroups = FeeGroup::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        $feeHeads = FeeHead::query()
            ->with('group')
            ->whereIn('fee_group_id', $feeGroups->pluck('id'))
            ->get();

        $existingFeeHeads = FeeHead::query()
            ->whereIn('fee_group_id', $newFeeGroups->pluck('id'))
            ->get();

        if ($existingFeeHeads->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('finance.fee_head.fee_head')])]);
        }

        \DB::beginTransaction();
        foreach ($feeHeads as $feeHead) {
            $newFeeGroup = $newFeeGroups->firstWhere('name', $feeHead->group->name);

            $newFeeHead = $feeHead->replicate();
            $newFeeHead->uuid = (string) Str::uuid();
            $newFeeHead->period_id = $currentPeriod->id;
            $newFeeHead->fee_group_id = $newFeeGroup->id;
            $newFeeHead->save();
        }
        \DB::commit();
    }

    public function importContactModule(Request $request, Team $team): void
    {
        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'contact')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'contact')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'contact',
                'value' => $config->value,
            ]);
        }

        $castes = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::MEMBER_CASTE)
            ->get();

        foreach ($castes as $caste) {
            $newCaste = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::MEMBER_CASTE,
                'name' => $caste->name,
            ]);

            $newCaste->description = $caste->description;
            $newCaste->meta = $caste->meta;
            $newCaste->save();
        }

        $categories = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::MEMBER_CATEGORY)
            ->get();

        foreach ($categories as $category) {
            $newCategory = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::MEMBER_CATEGORY,
                'name' => $category->name,
            ]);

            $newCategory->description = $category->description;
            $newCategory->meta = $category->meta;
            $newCategory->save();
        }

        $religions = Option::query()
            ->where('team_id', $request->team_id)
            ->where('type', OptionType::RELIGION)
            ->get();

        foreach ($religions as $religion) {
            $newReligion = Option::firstOrCreate([
                'team_id' => $team->id,
                'type' => OptionType::RELIGION,
                'name' => $religion->name,
            ]);

            $newReligion->description = $religion->description;
            $newReligion->meta = $religion->meta;
            $newReligion->save();
        }
    }

    public function importTransportModule(Request $request, Team $team): void
    {
        $currentPeriod = $this->getCurrentPeriod($team);

        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'transport')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'transport')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'transport',
                'value' => $config->value,
            ]);
        }

        $transportCircles = Circle::query()
            ->where('period_id', $request->period_id)
            ->get();

        $existingTransportCircles = Circle::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        if ($existingTransportCircles->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('transport.circle.circle')])]);
        }

        \DB::beginTransaction();
        foreach ($transportCircles as $transportCircle) {
            $newTransportCircle = $transportCircle->replicate();
            $newTransportCircle->uuid = (string) Str::uuid();
            $newTransportCircle->period_id = $currentPeriod->id;
            $newTransportCircle->save();
        }
        \DB::commit();

        $newTransportCircles = Circle::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        $transportFees = Fee::query()
            ->with('records.circle')
            ->where('period_id', $request->period_id)
            ->get();

        $existingTransportFees = Fee::query()
            ->where('period_id', $currentPeriod->id)
            ->get();

        if ($existingTransportFees->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('global.exists', ['attribute' => trans('transport.fee.fee')])]);
        }

        \DB::beginTransaction();
        foreach ($transportFees as $transportFee) {
            $newTransportFee = $transportFee->replicate();
            $newTransportFee->uuid = (string) Str::uuid();
            $newTransportFee->period_id = $currentPeriod->id;
            $newTransportFee->save();

            foreach ($transportFee->records as $record) {
                $transportCircle = $record->circle;

                $newTransportCircle = $newTransportCircles->firstWhere('name', $transportCircle->name);

                if (! $newTransportCircle) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('transport.circle.circle')])]);
                }

                $newRecord = $record->replicate();
                $newRecord->uuid = (string) Str::uuid();
                $newRecord->transport_fee_id = $newTransportFee->id;
                $newRecord->transport_circle_id = $newTransportCircle->id;
                $newRecord->save();
            }
        }
        \DB::commit();
    }

    public function importApprovalModule(Request $request, Team $team): void
    {
        $config = Config::query()
            ->where('team_id', $request->team_id)
            ->where('name', 'approval')
            ->first();

        $newConfig = Config::query()
            ->where('team_id', $team->id)
            ->where('name', 'approval')
            ->first();

        if (! $newConfig && $config) {
            $newConfig = Config::create([
                'team_id' => $team->id,
                'name' => 'approval',
                'value' => $config->value,
            ]);
        }

        $importFromTeamDepartments = EmployeeDepartment::query()
            ->globalOrByTeam($request->team_id)
            ->byTeam($request->team_id)
            ->get();

        $departments = EmployeeDepartment::query()
            ->globalOrByTeam($team->id)
            ->get();

        $approvalTypes = ApprovalType::query()
            ->with('department', 'levels')
            ->where('team_id', $request->team_id)
            ->get();

        $employee = Employee::query()
            ->select('id')
            ->where('team_id', $team->id)
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('employee.employee')])]);
        }

        \DB::beginTransaction();

        foreach ($approvalTypes as $approvalType) {
            $department = $approvalType->department_id ? $importFromTeamDepartments->firstWhere('name', $approvalType->department->name) : null;

            $newDepartmentId = null;
            if ($department) {
                $currentTeamDepartment = $departments->firstWhere('name', $department->name);

                if ($currentTeamDepartment) {
                    $newDepartmentId = $currentTeamDepartment->id;
                }
            }

            $newApprovalType = $approvalType->replicate();
            $newApprovalType->uuid = (string) Str::uuid();
            $newApprovalType->team_id = $team->id;
            $newApprovalType->department_id = $newDepartmentId;
            $newApprovalType->save();

            foreach ($approvalType->levels as $level) {
                $newLevel = $level->replicate();
                $newLevel->uuid = (string) Str::uuid();
                $newLevel->type_id = $newApprovalType->id;
                $newLevel->employee_id = $employee->id;

                $levelConfig = $level->config;
                $levelConfig['is_other_team_member'] = false;
                $newLevel->config = $levelConfig;

                $newLevel->save();
            }
        }

        \DB::commit();
    }
}
