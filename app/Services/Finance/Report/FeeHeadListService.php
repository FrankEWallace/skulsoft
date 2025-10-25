<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\Report\FeeHeadListResource;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class FeeHeadListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'name', 'total_fee', 'paid_fee', 'balance_fee', 'concession_fee'];

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
                'key' => 'totalAmount',
                'label' => trans('student.fee.props.amount'),
                'type' => 'currency',
                'print_label' => 'total.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'concessionAmount',
                'label' => trans('finance.fee.concession'),
                'type' => 'currency',
                'print_label' => 'concession.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'paidAmount',
                'label' => trans('finance.fee.paid'),
                'type' => 'currency',
                'print_label' => 'paid.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'balanceAmount',
                'label' => trans('finance.fee.balance'),
                'type' => 'currency',
                'print_label' => 'balance.formatted',
                'sortable' => true,
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
        $request->validate([
            'fee_head' => 'required',
        ]);

        return Student::query()
            ->summary()
            ->byPeriod()
            ->filterAccessible()
            ->selectRaw('SUM(student_fee_records.amount) as total_amount')
            ->selectRaw('SUM(student_fee_records.paid) as paid_amount')
            ->selectRaw('SUM(student_fee_records.concession) as concession_amount')
            ->selectRaw('SUM(student_fee_records.amount) - SUM(student_fee_records.paid) - SUM(student_fee_records.concession) as balance_amount')
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('student_fee_records', 'student_fees.id', '=', 'student_fee_records.student_fee_id')
            ->when(Str::isUuid($request->query('fee_head')), function ($q) use ($request) {
                $q->join('fee_heads', 'student_fee_records.fee_head_id', '=', 'fee_heads.id')
                    ->where('fee_heads.uuid', $request->query('fee_head'));
            }, function ($q) use ($request) {
                $q->where('student_fee_records.default_fee_head', $request->query('fee_head'));
            })
            ->havingRaw('SUM(student_fee_records.amount) > 0')
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
        $summary = Student::query()
            ->summaryWithoutSelect()
            ->byPeriod()
            ->filterAccessible()
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('student_fee_records', 'student_fees.id', '=', 'student_fee_records.student_fee_id')
            ->when(Str::isUuid($request->query('fee_head')), function ($q) use ($request) {
                $q->join('fee_heads', 'student_fee_records.fee_head_id', '=', 'fee_heads.id')
                    ->where('fee_heads.uuid', $request->query('fee_head'));
            }, function ($q) use ($request) {
                $q->where('student_fee_records.default_fee_head', $request->query('fee_head'));
            })
            ->selectRaw('SUM(student_fee_records.amount) as total_amount')
            ->selectRaw('SUM(student_fee_records.concession) as concession_amount')
            ->selectRaw('SUM(student_fee_records.paid) as paid_amount')
            ->selectRaw('SUM(student_fee_records.amount) - SUM(student_fee_records.paid) - SUM(student_fee_records.concession) as balance_amount')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ])
            ->first();

        return FeeHeadListResource::collection($this->filter($request)
            ->groupBy('students.id')
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Fee Head Report',
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
                    ['key' => 'totalAmount', 'label' => \Price::from($summary->total_amount)->formatted],
                    ['key' => 'concessionAmount', 'label' => \Price::from($summary->concession_amount)->formatted],
                    ['key' => 'paidAmount', 'label' => \Price::from($summary->paid_amount)->formatted],
                    ['key' => 'balanceAmount', 'label' => \Price::from($summary->balance_amount)->formatted],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
