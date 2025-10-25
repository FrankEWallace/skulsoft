<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\Report\FeeSummaryListResource;
use App\Models\Contact;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class FeeSummaryListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'name', 'total_fee', 'paid_fee', 'balance_fee', 'concession_fee'];

    protected $defaultSort = 'code_number';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'sno',
                'label' => trans('general.sno'),
                'sortable' => false,
                'visibility' => true,
            ],
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
                'key' => 'category',
                'label' => trans('contact.category.category'),
                'print_label' => 'category.name',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'totalFee',
                'label' => trans('finance.fee.total'),
                'type' => 'currency',
                'print_label' => 'total.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'concessionFee',
                'label' => trans('finance.fee.concession'),
                'type' => 'currency',
                'print_label' => 'concession.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'paidFee',
                'label' => trans('finance.fee.paid'),
                'type' => 'currency',
                'print_label' => 'paid.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'balanceFee',
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
        $minTotal = $request->query('min_total', 0);
        $minPaid = $request->query('min_paid', 0);
        $minConcession = $request->query('min_concession', 0);
        $minBalance = $request->query('min_balance', 0);

        $groups = Str::toArray($request->query('groups'));

        return Student::query()
            ->summary()
            ->byPeriod()
            ->filterAccessible()
            ->selectRaw('SUM(student_fees.total) as total_fee')
            ->selectRaw('SUM(student_fees.paid) as paid_fee')
            ->selectRaw('SUM(student_fees.total - student_fees.paid) as balance_fee')
            ->selectRaw('(SELECT SUM(student_fee_records.concession) FROM student_fee_records WHERE student_fee_records.student_fee_id IN (SELECT id FROM student_fees WHERE student_fees.student_id = students.id)) as concession_fee')
            ->leftJoin('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($minTotal, function ($q) use ($minTotal) {
                $q->havingRaw('SUM(student_fees.total) >= ?', [$minTotal]);
            })
            ->when($minPaid, function ($q) use ($minPaid) {
                $q->havingRaw('SUM(student_fees.paid) >= ?', [$minPaid]);
            })
            ->when($minConcession, function ($q) use ($minConcession) {
                $q->havingRaw('(SELECT SUM(student_fee_records.concession) FROM student_fee_records WHERE student_fee_records.student_fee_id IN (SELECT id FROM student_fees WHERE student_fees.student_id = students.id)) >= ?', [$minConcession]);
            })
            ->when($minBalance, function ($q) use ($minBalance) {
                $q->havingRaw('SUM(student_fees.total - student_fees.paid) >= ?', [$minBalance]);
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
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $minTotal = $request->query('min_total', 0);
        $minPaid = $request->query('min_paid', 0);
        $minConcession = $request->query('min_concession', 0);
        $minBalance = $request->query('min_balance', 0);

        $groups = Str::toArray($request->query('groups'));

        $summary = Student::query()
            ->summaryWithoutSelect()
            ->byPeriod()
            ->filterAccessible()
            ->where(function ($q) {
                $q->whereNull('admissions.leaving_date')
                    ->orWhere('admissions.leaving_date', '>', today()->toDateString());
            })
            ->leftJoin('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->selectRaw('SUM(student_fees.total) as total_fee')
            ->selectRaw('SUM(student_fees.paid) as paid_fee')
            ->selectRaw('SUM(student_fees.total - student_fees.paid) as balance_fee')
            ->selectRaw('SUM((SELECT SUM(concession) FROM student_fee_records WHERE student_fee_records.student_fee_id = student_fees.id)) as concession_fee')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
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
            // ->when($minTotal, function ($q) use ($minTotal) {
            //     $q->havingRaw('SUM(student_fees.total) >= ?', [$minTotal]);
            // })
            // ->when($minPaid, function ($q) use ($minPaid) {
            //     $q->havingRaw('SUM(student_fees.paid) >= ?', [$minPaid]);
            // })
            // ->when($minConcession, function ($q) use ($minConcession) {
            //     $q->havingRaw('SUM((SELECT SUM(concession) FROM student_fee_records WHERE student_fee_records.student_fee_id = student_fees.id)) >= ?', [$minConcession]);
            // })
            // ->when($minBalance, function ($q) use ($minBalance) {
            //     $q->havingRaw('SUM(student_fees.total - student_fees.paid) >= ?', [$minBalance]);
            // })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ])
            ->first();

        $records = $this->filter($request)
            ->groupBy('students.id')
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $contacts = Contact::query()
            ->whereIn('id', $records->pluck('contact_id'))
            ->with('category')
            ->get();

        $request->merge([
            'contacts' => $contacts,
        ]);

        return FeeSummaryListResource::collection($records)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Fee Summary Report',
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
                    ['key' => 'totalFee', 'label' => \Price::from($summary->total_fee)->formatted],
                    ['key' => 'concessionFee', 'label' => \Price::from($summary->concession_fee)->formatted],
                    ['key' => 'paidFee', 'label' => \Price::from($summary->paid_fee)->formatted],
                    ['key' => 'balanceFee', 'label' => \Price::from($summary->balance_fee)->formatted],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
