<?php

namespace App\Services\Dashboard;

use App\Enums\Employee\Type;
use App\Enums\ServiceType;
use App\Enums\Student\AttendanceSession;
use App\Domain\Academic\Models\Course;
use App\Models\Employee\Employee;
use App\Models\Finance\Transaction;
use App\Models\Student\Attendance;
use App\Models\Student\ServiceAllocation;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class StatService
{
    public function fetch()
    {
        $stats = [
            $this->getStudentStat(),
            $this->getEmployeeStat(),
        ];

        $attendanceSummary = $this->getAttendanceSummary();

        $feeSummary = $this->getFeeSummary();

        $feeCollectionSummary = $this->getFeeCollectionSummary();

        $studentStrengthChartData = $this->getCourseWiseStrengthChartData();

        $transactionChartData = $this->getTransactionChartData();

        $serviceSummary = $this->getServiceStat();

        return compact('stats', 'attendanceSummary', 'feeSummary', 'feeCollectionSummary', 'studentStrengthChartData', 'transactionChartData', 'serviceSummary');
    }

    private function getStudentStat()
    {
        $student = Student::query()
            ->byPeriod()
            ->filterStudying()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->selectRaw("count('id') as total")
            ->first();

        $newStudents = Student::query()
            ->byPeriod()
            ->filterStudying()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->whereColumn('admissions.joining_date', '=', 'students.start_date')
            ->selectRaw("count('id') as total")
            ->first();

        return [
            'title' => trans('student.student'),
            'sub_title' => trans('general.new', ['attribute' => trans('student.student')]),
            'count' => $student->total,
            'sub_title_count' => $newStudents->total,
            'icon' => 'fas fa-user-graduate',
            'color' => 'bg-success',
            'sub_title_icon' => 'fas fa-arrow-up',
            'sub_title_color' => 'bg-success',
            'total' => $student->total,
        ];
    }

    private function getServiceStat()
    {
        $types = ServiceType::getOptions();

        $availableServices = explode(',', config('config.student.services'));

        $types = collect($types)->filter(function ($type) use ($availableServices) {
            return in_array(Arr::get($type, 'value'), $availableServices);
        })->values()->toArray();

        $student = Student::query()
            ->byPeriod()
            ->filterStudying()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->selectRaw("count('id') as total")
            ->first();

        $serviceSummary = ServiceAllocation::query()
            ->selectRaw('service_allocations.type, COUNT(service_allocations.id) as total')
            ->join('students', 'students.id', '=', 'service_allocations.model_id')
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->whereNull('students.cancelled_at')->where(function ($q) {
                $q->whereNull('admissions.leaving_date')
                    ->orWhere('admissions.leaving_date', '>', today()->toDateString());
            })
            ->where('students.period_id', auth()->user()->current_period_id)
            ->whereIn('service_allocations.type', Arr::pluck($types, 'value'))
            ->groupBy('service_allocations.type')
            ->get()
            ->map(function ($item) use ($student) {
                $type = ServiceType::getDetail($item->type);

                $percent = $student->total ? round(($item->total / $student->total) * 100, 2) : 0;

                return [
                    'code' => Arr::get($type, 'value'),
                    'label' => Arr::get($type, 'label'),
                    'value' => $item->total,
                    'percent' => $percent,
                    'percentage' => \Percent::from($percent)->formatted,
                    'color' => \Percent::from($percent)->getPercentageColor(),
                    'max' => $student->total,
                ];
            });

        return $serviceSummary;
    }

    private function getEmployeeStat()
    {
        $employee = Employee::query()
            ->byTeam()
            ->selectRaw("count('id') as total")
            ->whereIn('type', [Type::ADMINISTRATIVE, Type::TEACHING])
            ->first();

        $allEmployee = Employee::query()
            ->byTeam()
            ->selectRaw("count('id') as total")
            ->first();

        return [
            'title' => trans('employee.employee'),
            'count' => $employee->total,
            'sub_count' => $allEmployee->total,
            'icon' => 'fas fa-user-tie',
            'color' => 'bg-info',
            'total' => $employee->total,
        ];
    }

    private function getAttendanceSummary()
    {
        $students = Student::query()
            ->basic()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->filterStudying()
            ->byPeriod()
            ->get();

        $batches = $students->pluck('batch_id')->all();

        $attendances = Attendance::query()
            ->whereIn('batch_id', $batches)
            ->where('date', '=', today()->toDateString())
            ->whereNull('subject_id')
            ->where('session', AttendanceSession::FIRST)
            ->whereIsDefault(1)
            ->get();

        $total = $students->count();
        $present = 0;
        $absent = 0;
        $late = 0;
        $halfDay = 0;

        foreach ($attendances as $attendance) {
            $count = collect($attendance->values)->flatMap(function ($item) {
                return [$item['code'] => count($item['uuids'])];
            })->all();

            $present += $count['P'] ?? 0;
            $absent += $count['A'] ?? 0;
            $late += $count['L'] ?? 0;
            $halfDay += $count['HD'] ?? 0;
        }

        $presentPercentage = $total ? round(($present / $total) * 100, 2) : 0;
        $absentPercentage = $total ? round(($absent / $total) * 100, 2) : 0;
        $latePercentage = $total ? round(($late / $total) * 100, 2) : 0;
        $halfDayPercentage = $total ? round(($halfDay / $total) * 100, 2) : 0;

        $attendanceSummary = [
            [
                'code' => 'present',
                'label' => trans('student.attendance.types.present'),
                'value' => $present,
                'percent' => $presentPercentage,
                'percentage' => \Percent::from($presentPercentage)->formatted,
                'color' => \Percent::from($presentPercentage)->getPercentageColor(),
                'max' => $total,
            ],
            [
                'code' => 'absent',
                'label' => trans('student.attendance.types.absent'),
                'value' => $absent,
                'percent' => $absentPercentage,
                'percentage' => \Percent::from($absentPercentage)->formatted,
                'color' => \Percent::from($absentPercentage)->getPercentageColor(),
                'max' => $total,
            ],
            [
                'code' => 'late',
                'label' => trans('student.attendance.types.late'),
                'value' => $late,
                'percent' => $latePercentage,
                'percentage' => \Percent::from($latePercentage)->formatted,
                'color' => \Percent::from($latePercentage)->getPercentageColor(),
                'max' => $total,
            ],
            [
                'code' => 'half_day',
                'label' => trans('student.attendance.types.half_day'),
                'value' => $halfDay,
                'percent' => $halfDayPercentage,
                'percentage' => \Percent::from($halfDayPercentage)->formatted,
                'color' => \Percent::from($halfDayPercentage)->getPercentageColor(),
                'max' => $total,
            ],
        ];

        return $attendanceSummary;
    }

    private function getFeeCollectionSummary()
    {
        $summary = Transaction::query()
            ->where('type', 'receipt')
            ->join('periods', 'periods.id', '=', 'transactions.period_id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->selectRaw('SUM(CASE WHEN date = ? THEN amount ELSE 0 END) as today_collection', [today()->toDateString()])
            ->selectRaw('SUM(CASE WHEN date BETWEEN ? AND ? THEN amount ELSE 0 END) as week_collection', [today()->subDays(6)->toDateString(), today()->toDateString()])
            ->selectRaw('SUM(CASE WHEN date BETWEEN ? AND ? THEN amount ELSE 0 END) as month_collection', [today()->subDays(30)->toDateString(), today()->toDateString()])
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('transactions.is_online', 0)
                        ->orWhere(function ($q) {
                            $q->where('transactions.is_online', 1)->whereNotNull('processed_at');
                        });
                })->whereNull('transactions.cancelled_at')->whereNull('transactions.rejected_at');
            })
            ->first();

        return [
            [
                'label' => trans('dashboard.today_collection'),
                'value' => \Price::from($summary->today_collection),
            ],
            [
                'label' => trans('dashboard.week_collection'),
                'value' => \Price::from($summary->week_collection),
            ],
            [
                'label' => trans('dashboard.month_collection'),
                'value' => \Price::from($summary->month_collection),
            ],
        ];
    }

    private function getFeeSummary()
    {
        $summary = Student::query()
            ->byPeriod()
            ->leftJoin('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->selectRaw('SUM(student_fees.total) as total_fee')
            ->selectRaw('SUM(student_fees.paid) as paid_fee')
            ->selectRaw('SUM(student_fees.total - student_fees.paid) as balance_fee')
            ->selectRaw('SUM((SELECT SUM(concession) FROM student_fee_records WHERE student_fee_records.student_fee_id = student_fees.id)) as concession_fee')
            ->first();

        $paidPercentage = $summary->total_fee > 0 ? round(($summary->paid_fee / $summary->total_fee) * 100, 2) : 0;
        $balancePercentage = $summary->total_fee > 0 ? round(($summary->balance_fee / $summary->total_fee) * 100, 2) : 0;
        $concessionPercentage = $summary->concession_fee > 0 ? round(($summary->concession_fee / $summary->concession_fee) * 100, 2) : 0;

        return [
            [
                'label' => trans('finance.fee.paid'),
                'value' => \Price::from($summary->paid_fee)->formatted,
                'percent' => $paidPercentage,
                'percentage' => \Percent::from($paidPercentage)->formatted,
                'color' => \Percent::from($paidPercentage)->getPercentageColor(),
                'max' => \Price::from($summary->total_fee)->formatted,
            ],
            [
                'label' => trans('finance.fee.balance'),
                'value' => \Price::from($summary->balance_fee)->formatted,
                'percent' => $balancePercentage,
                'percentage' => \Percent::from($balancePercentage)->formatted,
                'color' => \Percent::from($balancePercentage)->getPercentageColor(),
                'max' => \Price::from($summary->total_fee)->formatted,
            ],
            [
                'label' => trans('finance.fee.concession'),
                'value' => \Price::from($summary->concession_fee)->formatted,
                'percent' => $concessionPercentage,
                'percentage' => \Percent::from($concessionPercentage)->formatted,
                'color' => \Percent::from($concessionPercentage)->getPercentageColor(),
                'max' => \Price::from($summary->concession_fee)->formatted,
            ],
        ];
    }

    private function getCourseWiseStrengthChartData()
    {
        $courses = Course::query()
            ->byPeriod()
            ->leftJoin('batches', 'courses.id', '=', 'batches.course_id')
            ->leftJoin('students', 'batches.id', '=', 'students.batch_id')
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->whereNull('students.cancelled_at')
            ->where(function ($q) {
                $q->whereNull('admissions.leaving_date')
                    ->orWhere('admissions.leaving_date', '>', today()->toDateString());
            })
            ->select('courses.name as course_name', 'courses.term as course_term', \DB::raw('COUNT(students.id) as student_count'))
            ->groupBy('courses.id', 'courses.name', 'courses.term')
            ->orderBy('courses.position', 'asc')
            ->get();

        $labels = [];
        $data = [];
        foreach ($courses as $course) {
            $labels[] = $course->course_name.' '.$course->course_term;
            $data[] = $course->student_count;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => trans('academic.student_strength'),
                    'data' => $data,
                    'backgroundColor' => '#00CED1',
                    'borderColor' => '#00CED1',
                ],
            ],
        ];
    }

    private function getTransactionChartData()
    {
        $monthRanges = [];
        for ($i = 11; $i >= 0; $i--) {
            $startOfMonth = now()->subMonths($i)->startOfMonth();
            $endOfMonth = now()->subMonths($i)->endOfMonth();
            $monthRanges[] = [
                'start' => $startOfMonth->toDateString(),
                'end' => $endOfMonth->toDateString(),
            ];
        }

        $transactions = Transaction::selectRaw('MONTH(date) as month, SUM(CASE WHEN type = "receipt" THEN amount ELSE 0 END) as receipt, SUM(CASE WHEN type = "payment" THEN amount ELSE 0 END) as payment')
            ->whereHas('period', function ($q) {
                $q->where('team_id', auth()->user()->current_team_id);
            })
            ->whereIn('type', ['receipt', 'payment'])
            ->whereBetween('date', [$monthRanges[0]['start'], $monthRanges[11]['end']])
            ->whereNull('transactions.cancelled_at')
            ->where(function ($q) {
                $q->whereIsOnline(false)
                    ->orWhere(function ($q) {
                        $q->whereIsOnline(true)
                            ->whereNotNull('transactions.processed_at');
                    });
            })
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthData = [];
        foreach ($transactions as $transaction) {
            $monthData[$transaction->month] = [
                'receipt' => $transaction->receipt ?? 0,
                'payment' => $transaction->payment ?? 0,
            ];
        }

        $receiptData = [];
        $paymentData = [];
        foreach ($monthRanges as $monthRange) {
            $monthLabel = Carbon::parse($monthRange['start'])->format('F Y');
            $month = Carbon::parse($monthRange['start'])->format('n');
            $labels[] = $monthLabel;

            if (isset($monthData[$month])) {
                $receiptData[] = $monthData[$month]['receipt'];
                $paymentData[] = $monthData[$month]['payment'];
            } else {
                $receiptData[] = 0;
                $paymentData[] = 0;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => trans('finance.transaction.types.receipt'),
                    'data' => $receiptData,
                    'backgroundColor' => '#FF8C00',
                    'borderColor' => '#FF8C00',
                ],
                [
                    'label' => trans('finance.transaction.types.payment'),
                    'data' => $paymentData,
                    'backgroundColor' => '#483D8B',
                    'borderColor' => '#483D8B',
                ],
            ],
        ];
    }
}
