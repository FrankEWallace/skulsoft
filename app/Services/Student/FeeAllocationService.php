<?php

namespace App\Services\Student;

use App\Actions\Student\AssignFee;
use App\Actions\Student\FetchBatchWiseStudent;
use App\Enums\Gender;
use App\Enums\OptionType;
use App\Enums\Transport\Direction;
use App\Http\Resources\Finance\FeeConcessionResource;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\FeeAllocationResource;
use App\Http\Resources\Transport\CircleResource;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeHead;
use App\Models\Option;
use App\Models\Student\Fee;
use App\Models\Student\Student;
use App\Models\Transport\Circle;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeeAllocationService
{
    public function preRequisite(Request $request)
    {
        $directions = Direction::getOptions();

        $transportCircles = CircleResource::collection(Circle::query()
            ->byPeriod()
            ->get());

        $feeConcessions = FeeConcessionResource::collection(FeeConcession::query()
            ->byPeriod()
            ->get());

        $feeConcessionTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::FEE_CONCESSION_TYPE->value)
            ->get());

        $feeHeads = FeeHeadResource::collection(FeeHead::query()
            ->byPeriod()
            ->whereHas('group', function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('meta->is_custom')->orWhere('meta->is_custom', false);
                });
            })
            ->get());

        $groups = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_GROUP->value)
            ->get());

        return compact('directions', 'transportCircles', 'feeConcessions', 'feeConcessionTypes', 'feeHeads', 'groups');
    }

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.admission.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'joining_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'print_label' => 'gender.label',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'installmentCount',
                'label' => trans('finance.fee_structure.installment_count'),
                'print_label' => 'fees_count',
                'print_sub_label' => 'fee_concession_type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'contactNumber',
                'label' => trans('contact.props.contact_number'),
                'print_label' => 'contact_number',
                'print_sub_label' => 'email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'parent',
                'label' => trans('student.props.parent'),
                'print_label' => 'father_name',
                'print_sub_label' => 'mother_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'address',
                'label' => trans('contact.props.address.address'),
                'print_label' => 'short_address',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function fetch(Request $request)
    {
        $request->merge([
            'paginate' => true,
            'fees_count' => true,
            'with_fee_concession_type' => true,
        ]);
        $students = (new FetchBatchWiseStudent)->execute($request->all());

        return FeeAllocationResource::collection($students)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => ['name'],
                    'default_sort' => 'name',
                    'default_order' => 'asc',
                ],
            ]);
    }

    public function allocate(Request $request)
    {
        $selectAll = $request->boolean('select_all');

        if ($selectAll) {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);
        } else {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $feeAllocationBatch = Str::random(10);

        \DB::beginTransaction();

        foreach ($students as $student) {
            $student = Student::query()
                ->select('students.*', 'contacts.gender')
                ->join('contacts', 'students.contact_id', '=', 'contacts.id')
                ->where('students.uuid', Arr::get($student, 'uuid'))
                ->first();

            $isNewStudent = false;
            if ($student->joining_date == $student->start_date->value) {
                $isNewStudent = true;
            }

            $isMaleStudent = false;
            $isFemaleStudent = false;

            if ($student->gender == Gender::MALE->value) {
                $isMaleStudent = true;
            } elseif ($student->gender == Gender::FEMALE->value) {
                $isFemaleStudent = true;
            }

            if (! $student->fee_structure_id) {
                (new AssignFee)->execute(
                    student: $student,
                    feeConcession: $request->fee_concession,
                    transportCircle: $request->transport_circle,
                    params: [
                        'direction' => $request->direction,
                        'opted_fee_heads' => $request->opted_fee_heads,
                        'fee_allocation_batch' => $feeAllocationBatch,
                        'is_new_student' => $isNewStudent,
                        'is_male_student' => $isMaleStudent,
                        'is_female_student' => $isFemaleStudent,
                    ]
                );
            }
        }

        \DB::commit();
    }

    public function allocateFeeConcessionType(Request $request)
    {
        $selectAll = $request->boolean('select_all');

        if ($selectAll) {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);
        } else {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        \DB::beginTransaction();

        foreach ($students as $student) {
            $student = Student::whereUuid(Arr::get($student, 'uuid'))->first();

            $student->fee_concession_type_id = $request->fee_concession_type?->id;
            $student->save();
        }

        \DB::commit();
    }

    public function remove(Request $request)
    {
        $selectAll = $request->boolean('select_all');

        if ($selectAll) {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);
        } else {
            $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $students = Student::query()
            ->whereNotNull('fee_structure_id')
            ->whereIn('uuid', $request->students)
            ->get();

        if (! $students->count()) {
            throw ValidationException::withMessages(['message' => trans('student.fee.no_allocation_found')]);
        }

        $students->load('fees');

        \DB::beginTransaction();

        $removeCount = 0;
        foreach ($students as $student) {
            $paidFee = $student->fees->sum('paid.value');

            if ($paidFee) {
                continue;
            }

            Fee::whereStudentId($student->id)->delete();

            $student->fee_structure_id = null;
            $student->save();
            $removeCount++;
        }

        \DB::commit();

        if (! $removeCount) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_remove_paid_allocation')]);
        }
    }
}
