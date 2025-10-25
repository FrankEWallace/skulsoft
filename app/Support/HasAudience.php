<?php

namespace App\Support;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use App\Models\Academic\Division;
use App\Models\Audience;
use App\Models\Contact;
use App\Models\Employee\Department;
use App\Models\Employee\Designation;
use App\Models\Employee\Employee;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait HasAudience
{
    public function validateInput(array $params = [])
    {
        $studentAudienceType = Arr::get($params, 'student_audience_type');
        $employeeAudienceType = Arr::get($params, 'employee_audience_type');

        $studentAudiences = [];
        $employeeAudiences = [];

        if (Arr::get($params, 'is_public')) {
            $studentAudienceType = null;
            $employeeAudienceType = null;

            return compact('studentAudienceType', 'employeeAudienceType', 'studentAudiences', 'employeeAudiences');
        }

        if ($studentAudienceType == 'division_wise') {
            $divisions = Division::query()
                ->byPeriod()
                ->select('id', 'uuid')
                ->get();

            foreach (Arr::get($params, 'student_audiences', []) as $studentAudience) {
                $division = $divisions->firstWhere('uuid', $studentAudience);
                if (! $division) {
                    throw ValidationException::withMessages(['student_audiences' => __('global.could_not_find', ['attribute' => __('academic.division.division')])]);
                } else {
                    $studentAudiences[] = $division->id;
                }
            }
        } elseif ($studentAudienceType == 'course_wise') {
            $courses = Course::query()
                ->byPeriod()
                ->select('id', 'uuid')
                ->get();

            foreach (Arr::get($params, 'student_audiences', []) as $studentAudience) {
                $course = $courses->firstWhere('uuid', $studentAudience);
                if (! $course) {
                    throw ValidationException::withMessages(['student_audiences' => __('global.could_not_find', ['attribute' => __('academic.course.course')])]);
                } else {
                    $studentAudiences[] = $course->id;
                }
            }
        } elseif ($studentAudienceType == 'batch_wise') {
            $batches = Batch::query()
                ->byPeriod()
                ->select('id', 'uuid')
                ->get();

            foreach (Arr::get($params, 'student_audiences', []) as $studentAudience) {
                $batch = $batches->firstWhere('uuid', $studentAudience);
                if (! $batch) {
                    throw ValidationException::withMessages(['student_audiences' => __('global.could_not_find', ['attribute' => __('academic.batch.batch')])]);
                } else {
                    $studentAudiences[] = $batch->id;
                }
            }
        } elseif ($studentAudienceType == 'student_wise') {
            //
        }

        if ($employeeAudienceType == 'department_wise') {
            $departments = Department::query()
                ->globalOrByTeam()
                ->select('id', 'uuid')
                ->get();

            foreach (Arr::get($params, 'employee_audiences', []) as $employeeAudience) {
                $department = $departments->firstWhere('uuid', $employeeAudience);
                if (! $department) {
                    throw ValidationException::withMessages(['employee_audiences' => __('global.could_not_find', ['attribute' => __('employee.department.department')])]);
                } else {
                    $employeeAudiences[] = $department->id;
                }
            }
        } elseif ($employeeAudienceType == 'designation_wise') {
            $designations = Designation::query()
                ->byTeam()
                ->select('id', 'uuid')
                ->get();

            foreach (Arr::get($params, 'employee_audiences', []) as $employeeAudience) {
                $designation = $designations->firstWhere('uuid', $employeeAudience);
                if (! $designation) {
                    throw ValidationException::withMessages(['employee_audiences' => __('global.could_not_find', ['attribute' => __('employee.designation.designation')])]);
                } else {
                    $employeeAudiences[] = $designation->id;
                }
            }
        } elseif ($employeeAudienceType == 'employee_wise') {
            //
        }

        return compact('studentAudienceType', 'employeeAudienceType', 'studentAudiences', 'employeeAudiences');
    }

    public function storeAudience(Model $model, array $params = [])
    {
        $studentAudienceType = Arr::get($params, 'student_audience_type');
        $employeeAudienceType = Arr::get($params, 'employee_audience_type');
        $studentAudiences = Arr::get($params, 'student_audiences', []);
        $employeeAudiences = Arr::get($params, 'employee_audiences', []);

        if ($studentAudienceType == 'division_wise') {
            foreach ($studentAudiences as $division) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $division,
                    'audienceable_type' => 'Division',
                ]);
            }
        } elseif ($studentAudienceType == 'course_wise') {
            foreach ($studentAudiences as $course) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $course,
                    'audienceable_type' => 'Course',
                ]);
            }
        } elseif ($studentAudienceType == 'batch_wise') {
            foreach ($studentAudiences as $batch) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $batch,
                    'audienceable_type' => 'Batch',
                ]);
            }
        } elseif ($studentAudienceType == 'student_wise') {
            foreach ($studentAudiences as $student) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $student,
                    'audienceable_type' => 'Student',
                ]);
            }
        }

        if ($employeeAudienceType == 'department_wise') {
            foreach ($employeeAudiences as $department) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $department,
                    'audienceable_type' => 'Department',
                ]);
            }
        } elseif ($employeeAudienceType == 'designation_wise') {
            foreach ($employeeAudiences as $designation) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $designation,
                    'audienceable_type' => 'Designation',
                ]);
            }
        } elseif ($employeeAudienceType == 'employee_wise') {
            foreach ($employeeAudiences as $employee) {
                Audience::firstOrCreate([
                    'shareable_id' => $model->id,
                    'shareable_type' => $model->getModelName(),
                    'audienceable_id' => $employee,
                    'audienceable_type' => 'Employee',
                ]);
            }
        }
    }

    public function updateAudience(Model $model, array $params = [])
    {
        if ($model->is_public) {
            $model->audiences()->delete();

            return;
        }

        $studentAudienceType = Arr::get($model->audience, 'student_type');
        $employeeAudienceType = Arr::get($model->audience, 'employee_type');

        if ($studentAudienceType == 'all') {
            $model->audiences()->whereIn('audienceable_type', ['Division', 'Course', 'Batch'])->delete();
        }

        if ($employeeAudienceType == 'all') {
            $model->audiences()->whereIn('audienceable_type', ['Department', 'Designation'])->delete();
        }

        if ($studentAudienceType == 'all' && $employeeAudienceType == 'all') {
            return;
        }

        $model->audiences()->delete();

        $this->storeAudience($model, $params);
    }

    public function prepareAudienceForUpdate(Model $model, array $params = [])
    {
        if ($model->is_public && Arr::get($params, 'is_public')) {
            return;
        }
    }

    public function getContacts(array $params = [])
    {
        $studentAudienceType = Arr::get($params, 'student_audience_type');
        $employeeAudienceType = Arr::get($params, 'employee_audience_type');
        $studentAudiences = Arr::get($params, 'student_audiences', []);
        $employeeAudiences = Arr::get($params, 'employee_audiences', []);

        $students = collect([]);
        if ($studentAudienceType == 'all') {
            $students = Student::query()
                ->byPeriod()
                ->select('students.id', 'students.contact_id')
                ->get();
        } elseif ($studentAudienceType == 'division_wise') {
            $divisions = Division::query()
                ->byPeriod()
                ->whereIn('id', $studentAudiences)
                ->get();

            $students = Student::query()
                ->byPeriod()
                ->select('students.id', 'students.contact_id')
                ->join('batches', 'batches.id', 'students.batch_id')
                ->join('courses', 'courses.id', 'batches.course_id')
                ->join('divisions', 'divisions.id', 'courses.division_id')
                ->whereIn('divisions.uuid', $divisions->pluck('uuid')->toArray())
                ->get();
        } elseif ($studentAudienceType == 'course_wise') {
            $courses = Course::query()
                ->byPeriod()
                ->whereIn('id', $studentAudiences)
                ->get();

            $students = Student::query()
                ->byPeriod()
                ->select('students.id', 'students.contact_id')
                ->join('batches', 'batches.id', 'students.batch_id')
                ->join('courses', 'courses.id', 'batches.course_id')
                ->whereIn('courses.id', $courses->pluck('id')->toArray())
                ->get();
        } elseif ($studentAudienceType == 'batch_wise') {
            $batches = Batch::query()
                ->byPeriod()
                ->whereIn('id', $studentAudiences)
                ->get();

            $students = Student::query()
                ->byPeriod()
                ->select('students.id', 'students.contact_id')
                ->join('batches', 'batches.id', 'students.batch_id')
                ->whereIn('batches.id', $batches->pluck('id')->toArray())
                ->get();
        }

        $employees = collect([]);
        if ($employeeAudienceType == 'all') {
            $employees = Employee::query()
                ->byTeam()
                ->select('employees.id', 'employees.contact_id')
                ->get();
        } elseif ($employeeAudienceType == 'department_wise') {
            $departments = Department::query()
                ->globalOrByTeam()
                ->whereIn('id', $employeeAudiences)
                ->get();

            $employees = Employee::query()
                ->byTeam()
                ->select('employees.id', 'employees.contact_id')
                ->leftJoin('employee_records', function ($join) {
                    $join->on('employees.id', '=', 'employee_records.employee_id')
                        ->on('start_date', '=', \DB::raw("(select start_date from employee_records where employees.id = employee_records.employee_id and start_date <= '".today()->toDateString()."' order by start_date desc limit 1)"))
                        ->join('departments', 'employee_records.department_id', '=', 'departments.id');
                })
                ->whereIn('departments.id', $departments->pluck('id')->toArray())
                ->get();
        } elseif ($employeeAudienceType == 'designation_wise') {
            $designations = Designation::query()
                ->byTeam()
                ->whereIn('id', $employeeAudiences)
                ->get();

            $employees = Employee::query()
                ->byTeam()
                ->select('employees.id', 'employees.contact_id')
                ->leftJoin('employee_records', function ($join) {
                    $join->on('employees.id', '=', 'employee_records.employee_id')
                        ->on('start_date', '=', \DB::raw("(select start_date from employee_records where employees.id = employee_records.employee_id and start_date <= '".today()->toDateString()."' order by start_date desc limit 1)"))
                        ->join('designations', 'employee_records.designation_id', '=', 'designations.id');
                })
                ->whereIn('designations.id', $designations->pluck('id')->toArray())
                ->get();
        }

        $contacts = Contact::query()
            ->whereIn('id', $students->pluck('contact_id'))
            ->orWhereIn('id', $employees->pluck('contact_id'))
            ->get();

        return $contacts;
    }
}
