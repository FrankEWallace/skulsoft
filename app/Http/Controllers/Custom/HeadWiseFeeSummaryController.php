<?php

namespace App\Http\Controllers\Custom;

use App\Exports\ListExport;
use App\Http\Controllers\Controller;
use App\Models\Finance\FeeHead;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HeadWiseFeeSummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        if (empty($request->query('start'))) {
            return view('reports.finance.head-wise-fee-summary');
        }

        $start = $request->input('start', 1);
        $limit = $request->input('limit', 100);

        $request->validate([
            'start' => 'integer|min:1',
            'limit' => 'integer|min:1',
        ]);

        $feeHeads = FeeHead::query()
            ->byPeriod(auth()->user()->current_period_id)
            ->get();

        $students = Student::query()
            ->where('students.period_id', auth()->user()->current_period_id)
            ->select('students.id', 'students.uuid', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'contacts.contact_number', 'batches.name as batch_name', 'courses.name as course_name')
            ->selectRaw('SUM(student_fees.total) as total_fee')
            ->selectRaw('SUM(student_fees.paid) as paid_fee')
            ->selectRaw('SUM(student_fees.total - student_fees.paid) as balance_fee')
            ->leftJoin('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->where(function ($q) {
                $q->whereNull('admissions.leaving_date')
                    ->orWhere('admissions.leaving_date', '>', today()->toDateString());
            })
            ->take($limit)
            ->skip($start - 1)
            ->groupBy('students.id')
            ->orderBy('name', 'asc')
            ->get();

        $header = ['Name', 'Roll Number', 'Class', 'Contact Number', 'Total', 'Paid', 'Balance'];
        foreach ($feeHeads as $feeHead) {
            array_push($header, $feeHead->name);
            array_push($header, $feeHead->name.' Paid');
        }

        array_push($header, 'Transport Fee');
        array_push($header, 'Transport Fee Paid');
        array_push($header, 'Late Fee');
        array_push($header, 'Late Fee Paid');

        $records = FeeRecord::query()
            ->join('student_fees', 'student_fee_records.student_fee_id', '=', 'student_fees.id')
            ->select(
                'student_id', 'fee_head_id', 'default_fee_head',
                \DB::raw('SUM(student_fee_records.amount) as total_amount'),
                \DB::raw('SUM(student_fee_records.paid) as total_paid')
            )
            ->whereIn('student_id', $students->pluck('id')->all())
            ->groupBy('student_id', 'fee_head_id', 'default_fee_head')
            ->get();

        $rows = [$header];
        foreach ($students as $student) {
            $row = [];

            array_push($row, $student->name);
            array_push($row, $student->code_number);
            array_push($row, $student->course_name.' '.$student->batch_name);
            array_push($row, $student->contact_number);
            array_push($row, $student->total_fee);
            array_push($row, $student->paid_fee);
            array_push($row, $student->balance_fee);

            foreach ($feeHeads as $feeHead) {
                $record = $records->where('student_id', $student->id)
                    ->where('fee_head_id', $feeHead->id)
                    ->first();
                if ($record) {
                    $row[] = $record->total_amount;
                    $row[] = $record->total_paid;
                } else {
                    $row[] = 0;
                    $row[] = 0;
                }
            }

            $transportFee = $records->where('student_id', $student->id)
                ->where('default_fee_head.value', 'transport_fee')
                ->first();

            if ($transportFee) {
                $row[] = $transportFee->total_amount;
                $row[] = $transportFee->total_paid;
            } else {
                $row[] = 0;
                $row[] = 0;
            }

            $lateFee = $records->where('student_id', $student->id)
                ->where('default_fee_head.value', 'late_fee')
                ->first();

            if ($lateFee) {
                $row[] = $lateFee->total_amount;
                $row[] = $lateFee->total_paid;
            } else {
                $row[] = 0;
                $row[] = 0;
            }

            array_push($rows, $row);
        }

        return Excel::download(new ListExport($rows), 'download.xlsx');
    }
}
