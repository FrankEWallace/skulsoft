<?php

namespace App\Services\Student;

use App\Actions\Student\AssignFee;
use App\Actions\Student\FetchStudentForPromotion;
use App\Enums\Gender;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Student\PromotionResource;
use App\Domain\Academic\Models\Period;
use App\Models\Finance\FeeAllocation;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeHead;
use App\Models\Student\Student;
use App\Models\Transport\Circle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function preRequisite(Request $request)
    {
        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->where('id', '!=', auth()->user()->current_period_id)
            ->get());

        return compact('periods');
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
                'key' => 'birthDate',
                'label' => trans('contact.props.birth_date'),
                'print_label' => 'birth_date.formatted',
                'sortable' => true,
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
        ];

        if (request()->ajax()) {
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function fetch(Request $request)
    {
        $students = (new FetchStudentForPromotion)->execute($request->all());

        return PromotionResource::collection($students)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => ['name'],
                    'default_sort' => 'name',
                    'default_order' => 'asc',
                ],
            ]);
    }

    public function store(Request $request)
    {
        $selectAll = $request->boolean('select_all');

        if ($selectAll) {
            $students = (new FetchStudentForPromotion)->execute($request->all(), true);
        } else {
            $students = Arr::get((new FetchStudentForPromotion)->execute($request->all(), true), 'data', []);

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $uniqueStudents = array_unique(Arr::pluck($students, 'code_number'));

        if (count($uniqueStudents) !== count($students)) {
            throw ValidationException::withMessages(['message' => trans('student.promotion.duplicate_student_found')]);
        }

        $alumniBatch = (string) Str::uuid();

        if ($request->boolean('mark_as_alumni')) {
            \DB::beginTransaction();

            foreach ($students as $student) {
                $student = Student::whereUuid(Arr::get($student, 'uuid'))->first();
                $student->setMeta([
                    'is_alumni' => true,
                    'alumni_date' => $request->date,
                ]);
                $student->end_date = $request->date;
                $student->save();

                $admission = $student->admission;
                $admission->leaving_date = $request->date;
                $admission->leaving_remarks = trans('student.alumni.marked_as_alumni');
                $admission->setMeta([
                    'alumni_batch' => $alumniBatch,
                ]);
                $admission->save();
            }

            \DB::commit();

            return;
        }

        $promotionBatch = (string) Str::uuid();

        if ($request->boolean('assign_fee')) {
            $feeAllocation = FeeAllocation::query()
                ->whereBatchId($request->batch_id)
                ->first() ?? FeeAllocation::query()
                ->whereCourseId($request->course_id)
                ->first();

            if ($feeAllocation) {
                $feeAllocation->load(
                    'structure.installments.records',
                    'structure.installments.transportFee.records',
                );
            }

            $transportCircles = Circle::query()
                ->wherePeriodId(auth()->user()->current_period_id)
                ->get();

            $newTransportCircles = Circle::query()
                ->wherePeriodId($request->period_id)
                ->get();

            $feeConcessions = FeeConcession::query()
                ->wherePeriodId(auth()->user()->current_period_id)
                ->get();

            $newFeeConcessions = FeeConcession::query()
                ->wherePeriodId($request->period_id)
                ->get();

            $newFeeHeads = FeeHead::query()
                ->wherePeriodId($request->period_id)
                ->whereHas('group', function ($q) {
                    $q->whereNull('meta->is_custom')
                        ->orWhere('meta->is_custom', '!=', true);
                })
                ->get();
        }

        \DB::beginTransaction();

        foreach ($students as $student) {
            $student = Student::query()
                ->select('students.*', 'contacts.gender')
                ->join('contacts', 'students.contact_id', '=', 'contacts.id')
                ->where('students.uuid', Arr::get($student, 'uuid'))
                ->first();

            $student->end_date = $request->date;
            $student->save();

            $newStudent = Student::forceCreate([
                'admission_id' => $student->admission_id,
                'period_id' => $request->period_id,
                'batch_id' => $request->batch_id,
                'contact_id' => $student->contact_id,
                'start_date' => $request->date,
                'meta' => [
                    'previous_student_id' => $student->id,
                    'promotion_batch' => $promotionBatch,
                ],
            ]);

            $user = User::find($student->contact?->user_id);

            if ($user) {
                $preference = $user->preference;
                $preference['academic']['period_id'] = $request->period_id;
                $user->preference = $preference;
                $user->save();
            }

            if (! $request->boolean('assign_fee')) {
                continue;
            }

            if (! $feeAllocation) {
                continue;
            }

            $student->load('fees.installment.group', 'fees.records.head');

            $optedFeeHeads = [];
            $newTransportCircle = null;
            $newTransportDirection = null;
            $newFeeConcession = null;

            $feeGroups = $student->fees->filter(function ($fee) {
                return ! $fee->installment->group->getMeta('is_custom');
            })
                ->map(function ($fee) {
                    return $fee->installment->group->name;
                })
                ->unique()
                ->all();

            foreach ($feeGroups as $feeGroup) {
                $fees = $student->fees->filter(function ($fee) use ($feeGroup) {
                    return $fee->installment->group->name === $feeGroup;
                });

                $lastFee = $fees->map(function ($fee) {
                    if (empty($fee->due_date->value)) {
                        $fee->due_date = $fee->installment->due_date;
                    }

                    return $fee;
                })->sortByDesc('due_date.value')->first();

                if ($lastFee) {
                    if (is_null($newTransportCircle) && $lastFee->transport_circle_id) {
                        $transportCircle = $transportCircles->firstWhere('id', $lastFee->transport_circle_id);

                        if ($transportCircle) {
                            $newTransportCircle = $newTransportCircles->firstWhere('name', $transportCircle->name);
                            $newTransportDirection = $lastFee->transport_direction;
                        }
                    }

                    if (is_null($newFeeConcession) && $lastFee->fee_concession_id) {
                        $feeConcession = $feeConcessions->firstWhere('id', $lastFee->fee_concession_id);

                        if ($feeConcession) {
                            $newFeeConcession = $newFeeConcessions->firstWhere('name', $feeConcession->name);
                        }
                    }

                    foreach ($lastFee->records->where('fee_head_id', '!=', null) as $feeRecord) {
                        $optedFeeHeads[] = $newFeeHeads->firstWhere('name', $feeRecord->head->name)?->uuid;
                    }
                }
            }

            $optedFeeHeads = array_unique($optedFeeHeads);

            (new AssignFee)->execute(
                feeAllocation: $feeAllocation,
                student: $newStudent,
                feeConcession: $newFeeConcession,
                transportCircle: $newTransportCircle,
                params: [
                    'direction' => $newTransportDirection,
                    'opted_fee_heads' => $optedFeeHeads,
                    'fee_allocation_batch' => $promotionBatch,
                    'is_new_student' => false,
                    'is_male_student' => $newStudent->gender == Gender::MALE->value ? true : false,
                    'is_female_student' => $newStudent->gender == Gender::FEMALE->value ? true : false,
                ]
            );
        }

        \DB::commit();
    }

    public function cancel(Request $request)
    {
        if (! $request->query('batch')) {
            return 'Please enter batch.';
        }

        $students = Student::query()
            ->with('fees')
            ->where('meta->promotion_batch', $request->query('batch'))
            ->get();

        if (! $students->count()) {
            return 'No student found.';
        }

        if (! $request->query('confirm')) {
            return $students->count().' students found. Please confirm to cancel promotion.';
        }

        $count = 0;
        foreach ($students as $student) {
            $feeSummary = $student->getFeeSummary();

            if (! Arr::get($feeSummary, 'paid_fee')?->value) {
                $previousStudentId = $student->getMeta('previous_student_id');

                \DB::beginTransaction();

                Student::query()
                    ->where('id', $previousStudentId)
                    ->update(['end_date' => null]);

                $student->delete();

                \DB::commit();

                $count++;
            }
        }

        return $count.' Promotion cancelled.';
    }
}
