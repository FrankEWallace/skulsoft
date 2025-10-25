<?php

namespace App\Imports\Student;

use App\Actions\Student\CreateCustomFeeHead;
use App\Concerns\ItemImport;
use App\Helpers\CalHelper;
use App\Models\Finance\FeeHead;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CustomFeeImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 500;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('student_custom_fee');

        $errors = $this->validate($rows);

        $this->checkForErrors('student_custom_fee', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        activity()->disableLogging();

        $students = Student::query()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->byPeriod()
            ->select('students.*', 'admissions.code_number', 'admissions.leaving_date')
            ->get();

        $customFeeHeads = FeeHead::query()
            ->byPeriod()
            ->whereHas('group', function ($q) {
                $q->where('meta->is_custom', true);
            })
            ->get();

        foreach ($rows as $row) {
            $student = $students->firstWhere('code_number', Arr::get($row, 'admission_number'));
            $feeHead = $customFeeHeads->firstWhere('name', Arr::get($row, 'fee_head'));

            if (is_int(Arr::get($row, 'due_date'))) {
                $dueDate = Date::excelToDateTimeObject(Arr::get($row, 'due_date'))->format('Y-m-d');
            } else {
                $dueDate = Carbon::parse(Arr::get($row, 'due_date'))->toDateString();
            }

            (new CreateCustomFeeHead)->execute($student, [
                'fee_head_id' => $feeHead->id,
                'amount' => Arr::get($row, 'amount'),
                'due_date' => $dueDate,
                'remarks' => Arr::get($row, 'remarks'),
            ]);
        }

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $errors = [];

        $students = Student::query()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->byPeriod()
            ->select('students.id', 'admissions.code_number', 'admissions.leaving_date', 'fee_structure_id')
            ->get();

        $customFeeHeads = FeeHead::query()
            ->byPeriod()
            ->whereHas('group', function ($q) {
                $q->where('meta->is_custom', true);
            })
            ->get();

        foreach ($rows as $index => $row) {
            $rowNo = (int) $index + 2;

            $codeNumber = Arr::get($row, 'admission_number');
            $dueDate = Arr::get($row, 'due_date');
            $feeHeadName = Arr::get($row, 'fee_head');
            $amount = Arr::get($row, 'amount');
            $remarks = Arr::get($row, 'remarks');

            if (! $codeNumber) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'required');
            }

            $student = $students->where('code_number', $codeNumber)->first();

            if ($codeNumber && ! $student) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'invalid');
            }

            if ($codeNumber && ! $student?->fee_structure_id) {
                $errors[] = $this->setError($rowNo, trans('student.fee.fee'), 'custom', ['message' => trans('student.fee.set_fee_info')]);
            }

            if (! $dueDate) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.due_date'), 'required');
            }

            if (is_int($dueDate)) {
                $dueDate = Date::excelToDateTimeObject($dueDate)->format('Y-m-d');
            }

            if ($dueDate && ! CalHelper::validateDate($dueDate)) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.due_date'), 'invalid');
            }

            if (! $feeHeadName) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_head.fee_head'), 'required');
            }

            if ($feeHeadName && ! $customFeeHeads->where('name', $feeHeadName)->first()) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_head.fee_head'), 'invalid');
            }

            if (empty($amount)) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.amount'), 'required');
            }

            if ($amount && ! is_numeric($amount)) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.amount'), 'numeric');
            }

            if ($amount && $amount < 0) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.amount'), 'min', ['min' => 0]);
            }

            if ($remarks && strlen($remarks) > 500) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.remarks'), 'max', ['max' => 500]);
            }
        }

        return $errors;
    }
}
