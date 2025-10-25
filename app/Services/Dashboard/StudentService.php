<?php

namespace App\Services\Dashboard;

use App\Enums\ServiceType;
use App\Http\Resources\Dashboard\StudentFeeResource;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Models\Academic\Batch;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Student\Fee;
use App\Models\Student\ServiceAllocation;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StudentService
{
    public function fetch(Request $request)
    {
        $studentUuid = $request->query('student_uuid');

        $students = Student::query()
            ->byPeriod()
            ->summary()
            ->filterForStudentAndGuardian()
            ->when($studentUuid, function ($query, $studentUuid) {
                $query->where('students.uuid', $studentUuid);
            })
            ->orderBy('name', 'asc')
            ->get();

        $mentors = Employee::query()
            ->summary()
            ->whereIn('employees.id', $students->pluck('mentor_id')->all())
            ->get();

        $showStudent = $request->query('filter') == true && $request->query('show_student') == true;

        $serviceAllocations = ServiceAllocation::query()
            ->with('transportStoppage')
            ->whereIn('model_id', $students->pluck('id')->all())
            ->where('model_type', 'Student')
            ->get();

        $incharges = Incharge::query()
            ->whereHasMorph(
                'model',
                [Batch::class],
                function (Builder $query) use ($students) {
                    $query->whereIn('id', $students->pluck('batch_id')->all());
                }
            )
            ->where('start_date', '<=', today()->toDateString())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', today()->toDateString());
            })
            ->with(['employee' => fn ($q) => $q->detail()])
            ->get();

        foreach ($students as $student) {
            $services = $serviceAllocations->where('model_id', $student->id);

            $student->has_services = true;
            $student->services = $services->map(function ($service) {
                if ($service->type == ServiceType::TRANSPORT) {
                    return [
                        'type' => ServiceType::getDetail($service->type),
                        'transport_stoppage' => $service->transportStoppage?->name,
                    ];
                } else {
                    return [
                        'type' => ServiceType::getDetail($service->type),
                    ];
                }
            })->values()->toArray();

            $mentor = $mentors->firstWhere('id', $student->mentor_id);

            $student->has_mentor = true;
            $student->mentor = $mentor ? [
                'name' => $mentor->name,
                'designation' => $mentor->designation_name,
                'contact_number' => $mentor->contact_number,
            ] : null;

            $student->has_incharge = true;
            $student->incharges = $incharges->filter(function ($incharge) use ($student) {
                return $incharge->model->id == $student->batch_id;
            })->map(function ($incharge) {
                return [
                    'name' => $incharge->employee->name,
                    'designation' => $incharge->employee->designation_name,
                    'contact_number' => $incharge->employee->contact_number,
                ];
            });
        }

        if ($showStudent) {
            return [
                'students' => StudentSummaryResource::collection($students),
            ];
        }

        $fees = Fee::query()
            ->select('student_fees.id', 'student_fees.uuid', 'student_fees.student_id', 'students.uuid as student_uuid', 'student_fees.fee', 'student_fees.total', 'student_fees.paid', \DB::raw('total - paid as balance'), \DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date) as final_due_date'), 'fee_installments.title as installment_title', 'fee_groups.name as fee_group_name', 'fee_installments.late_fee as installment_late_fee')
            ->join('fee_installments', function ($join) {
                $join->on('student_fees.fee_installment_id', '=', 'fee_installments.id')
                    ->join('fee_groups', function ($join) {
                        $join->on('fee_installments.fee_group_id', '=', 'fee_groups.id');
                    });
            })
            ->join('students', function ($join) {
                $join->on('student_fees.student_id', '=', 'students.id');
            })
            ->when($studentUuid, function ($query, $studentUuid) {
                $query->where('students.uuid', $studentUuid);
            })
            ->whereIn('student_id', $students->pluck('id')->all())
            ->orderBy('final_due_date', 'asc')
            ->get();

        return [
            'students' => StudentSummaryResource::collection($students),
            'fees' => StudentFeeResource::collection($fees),
        ];
    }
}
