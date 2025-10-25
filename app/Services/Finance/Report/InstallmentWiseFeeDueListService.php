<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Helpers\CalHelper;
use App\Http\Resources\Finance\Report\InstallmentWiseFeeDueListResource;
use App\Models\Student\Fee;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstallmentWiseFeeDueListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'name', 'due_fee', 'final_due_date'];

    protected $defaultSort = 'code_number';

    protected $defaultOrder = 'asc';

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
                'key' => 'installment',
                'label' => trans('finance.fee_structure.installment'),
                'print_label' => 'installment_title',
                'print_sub_label' => 'fee_group_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'dueFee',
                'label' => trans('finance.fee.due'),
                'print_label' => 'due_fee.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'finalDueDate',
                'label' => trans('finance.fee_structure.props.due_date'),
                'type' => 'currency',
                'print_label' => 'final_due_date.formatted',
                'sortable' => true,
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

        if (! CalHelper::validateDate($dueOn)) {
            $dueOn = today()->toDateString();
        }

        return Fee::query()
            ->select('student_fees.id', 'student_fees.student_id', 'student_fees.total', 'student_fees.paid', \DB::raw('total - paid as due_fee'), \DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date) as final_due_date'), 'fee_installments.title as installment_title', 'fee_groups.name as fee_group_name', 'students.roll_number', 'students.batch_id', 'students.contact_id', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'contacts.father_name', 'contacts.contact_number')
            ->join('fee_installments', function ($join) use ($request) {
                $join->on('student_fees.fee_installment_id', '=', 'fee_installments.id')
                    ->join('fee_groups', function ($join) use ($request) {
                        $join->on('fee_installments.fee_group_id', '=', 'fee_groups.id')
                            ->when($request->query('fee_group'), function ($q, $feeGroup) {
                                $q->where('fee_groups.uuid', $feeGroup);
                            });
                    });
            })
            ->join('students', function ($join) {
                $join->on('student_fees.student_id', '=', 'students.id')
                    ->join('contacts', function ($join) {
                        $join->on('students.contact_id', '=', 'contacts.id')
                            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
                    })
                    ->join('batches', function ($join) {
                        $join->on('students.batch_id', '=', 'batches.id')
                            ->leftJoin('courses', function ($join) {
                                $join->on('batches.course_id', '=', 'courses.id');
                            });
                    })
                    ->join('admissions', function ($join) {
                        $join->on('students.admission_id', '=', 'admissions.id');
                    });
            })
            ->where('total', '>', \DB::raw('paid'))
            ->whereDate(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date)'), '<=', $dueOn)
            ->when($request->query('installment'), function ($q, $installment) {
                $q->where('fee_installments.title', 'like', "%{$installment}%");
            })
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $dueOn = $request->query('due_on');

        if (! CalHelper::validateDate($dueOn)) {
            $dueOn = today()->toDateString();
        }

        $studentIds = Student::query()
            ->select('students.id')
            ->byPeriod()
            ->filterAccessible()
            ->pluck('id')
            ->all();

        $summary = Fee::query()
            ->join('fee_installments', function ($join) use ($request) {
                $join->on('student_fees.fee_installment_id', '=', 'fee_installments.id')
                    ->join('fee_groups', function ($join) use ($request) {
                        $join->on('fee_installments.fee_group_id', '=', 'fee_groups.id')
                            ->when($request->query('fee_group'), function ($q, $feeGroup) {
                                $q->where('fee_groups.uuid', $feeGroup);
                            });
                    });
            })
            ->join('students', function ($join) {
                $join->on('student_fees.student_id', '=', 'students.id')
                    ->join('contacts', function ($join) {
                        $join->on('students.contact_id', '=', 'contacts.id')
                            ->where('contacts.team_id', '=', auth()->user()?->current_team_id);
                    })
                    ->join('batches', function ($join) {
                        $join->on('students.batch_id', '=', 'batches.id')
                            ->leftJoin('courses', function ($join) {
                                $join->on('batches.course_id', '=', 'courses.id');
                            });
                    })
                    ->join('admissions', function ($join) {
                        $join->on('students.admission_id', '=', 'admissions.id');
                    });
            })
            ->where('total', '>', \DB::raw('paid'))
            ->whereDate(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date)'), '<=', $dueOn)
            ->selectRaw('SUM(total - paid) as due_fee')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ])
            ->first();

        return InstallmentWiseFeeDueListResource::collection($this->filter($request)
            ->whereIn('student_id', $studentIds)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Installment Wise Fee Due Report',
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
                    ['key' => 'dueFee', 'label' => \Price::from($summary->due_fee)->formatted],
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
