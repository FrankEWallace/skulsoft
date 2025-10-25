<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Helpers\CalHelper;
use App\Http\Resources\Finance\Report\FeeDueListResource;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeeDueListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'name', 'due_fee'];

    protected $defaultSort = 'due_fee';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.admission.props.code_number'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('student.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'fatherName',
                'label' => trans('contact.props.father_name'),
                'print_label' => 'father_name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'feeGroup',
                'label' => trans('finance.fee_group.fee_group'),
                'print_label' => 'fee_group_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'dueFee',
                'label' => trans('finance.fee.due'),
                'type' => 'currency',
                'print_label' => 'due_fee.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'finalDueDate',
                'label' => trans('finance.fee_structure.props.due_date'),
                'print_label' => 'final_due_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'overdueBy',
                'label' => trans('finance.report.fee_due.props.overdue_by'),
                'print_label' => 'overdue_by',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $dueOn = $request->query('due_on');

        $groups = Str::toArray($request->query('groups'));

        if (! CalHelper::validateDate($dueOn)) {
            $dueOn = today()->toDateString();
        }

        return Student::query()
            ->select(
                'students.id',
                'fee_groups.name as fee_group_name',
                \DB::raw('SUM(student_fees.total - student_fees.paid) as due_fee'),
                \DB::raw('(SELECT MAX(COALESCE(student_fees.due_date, fee_installments.due_date))) as final_due_date'),
                \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'),
                'admissions.code_number',
                'admissions.joining_date',
                'admissions.leaving_date',
                'batches.uuid as batch_uuid',
                'batches.name as batch_name',
                'courses.uuid as course_uuid',
                'courses.name as course_name',
                'contacts.father_name',
                'contacts.contact_number'
            )
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('fee_installments', 'student_fees.fee_installment_id', '=', 'fee_installments.id')
            ->join('fee_groups', 'fee_installments.fee_group_id', '=', 'fee_groups.id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
            ->where('students.period_id', auth()->user()->current_period_id)
            ->whereDate(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date)'), '<=', $dueOn)
            ->havingRaw('SUM(student_fees.total - student_fees.paid) > 0')
            ->when($request->query('installment'), function ($q, $installment) {
                $q->where('fee_installments.title', 'like', "%{$installment}%");
            })
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('fee_group'), function ($q, $feeGroup) {
                $q->where('fee_groups.uuid', $feeGroup);
            })
            ->when($request->query('min_due'), function ($q, $minDue) {
                $q->havingRaw('SUM(student_fees.total - student_fees.paid) >= ?', [$minDue]);
            })
            ->when($groups, function ($q, $groups) {
                $q->leftJoin('group_members', 'students.id', 'group_members.model_id')
                    ->where('group_members.model_type', 'Student')
                    ->join('options as student_groups', 'group_members.model_group_id', 'student_groups.id')
                    ->whereIn('student_groups.uuid', $groups);
            })
            ->when($request->query('address'), function ($q, $address) {
                $q->filterByAddress($address);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ])
            ->groupBy('students.id', 'fee_groups.name');
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $dueOn = $request->query('due_on');

        $groups = Str::toArray($request->query('groups'));

        if (! CalHelper::validateDate($dueOn)) {
            $dueOn = today()->toDateString();
        }

        $studentIds = Student::query()
            ->select('students.id')
            ->byPeriod()
            ->filterAccessible()
            ->pluck('id')
            ->all();

        if (! count($studentIds)) {
            throw ValidationException::withMessages([
                'message' => trans('global.could_not_find', ['attribute' => trans('student.student')]),
            ]);
        }

        $summary = Student::query()
            ->select(\DB::raw('SUM(student_fees.total - student_fees.paid) as due_fee'))
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('fee_installments', 'student_fees.fee_installment_id', '=', 'fee_installments.id')
            ->join('fee_groups', 'fee_installments.fee_group_id', '=', 'fee_groups.id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->where('students.period_id', auth()->user()->current_period_id)
            ->whereDate(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date)'), '<=', $dueOn)
            ->havingRaw('SUM(student_fees.total - student_fees.paid) > 0')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('fee_group'), function ($q, $feeGroup) {
                $q->where('fee_groups.uuid', $feeGroup);
            })
            ->when($request->query('min_due'), function ($q, $minDue) {
                $q->havingRaw('SUM(student_fees.total - student_fees.paid) >= ?', [$minDue]);
            })
            ->when($groups, function ($q, $groups) {
                $q->leftJoin('group_members', 'students.id', 'group_members.model_id')
                    ->where('group_members.model_type', 'Student')
                    ->join('options as student_groups', 'group_members.model_group_id', 'student_groups.id')
                    ->whereIn('student_groups.uuid', $groups);
            })
            ->when($request->query('address'), function ($q, $address) {
                $q->filterByAddress($address);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ])
            ->first();

        return FeeDueListResource::collection($this->filter($request)
            ->whereIn('student_id', $studentIds)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Fee Due Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                    'has_footer' => true,
                ],
                'footers' => [
                    ['key' => 'codeNumber', 'label' => trans('general.total')],
                    ['key' => 'name', 'label' => ''],
                    ['key' => 'fatherName', 'label' => ''],
                    ['key' => 'course', 'label' => ''],
                    ['key' => 'installment', 'label' => ''],
                    ['key' => 'dueFee', 'label' => \Price::from($summary?->due_fee)->formatted],
                    ['key' => 'finalDueDate', 'label' => ''],
                    ['key' => 'overdueBy', 'label' => ''],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
