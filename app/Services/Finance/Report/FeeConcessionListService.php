<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\Report\FeeConcessionListResource;
use App\Models\Student\Fee;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeeConcessionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'name'];

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
                'key' => 'concession',
                'label' => trans('finance.fee.concession'),
                'print_label' => 'concession_name',
                'print_sub_label' => 'concession_type',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'detail',
                'label' => trans('general.detail'),
                'type' => 'array',
                'print_label' => 'records',
                'print_key' => 'fee_head_name',
                'print_sub_key' => 'concession.formatted',
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
        return Fee::query()
            ->select('student_fees.id', 'student_fees.uuid', 'student_fees.student_id', 'student_fees.due_date', 'fee_concessions.name as concession_name', 'fee_installments.title as installment_title', 'fee_groups.name as fee_group_name', 'students.roll_number', 'students.batch_id', 'students.contact_id', 'fee_concession_types.name as concession_type', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'contacts.father_name', 'contacts.contact_number')
            ->with([
                'payments' => function ($q) use ($request) {
                    $q->where('concession_amount', '>', 0)
                        ->whereHas('transaction', function ($q) use ($request) {
                            $q->whereNull('transactions.cancelled_at')->whereNull('transactions.rejected_at')->where(function ($q) {
                                $q->where('is_online', 0)
                                    ->orWhere(function ($q) {
                                        $q->where('is_online', 1)->whereNotNull('processed_at');
                                    });
                            })->when($request->query('start_date') && $request->query('end_date'), function ($q) use ($request) {
                                $q->whereBetween('transactions.date', [$request->query('start_date'), $request->query('end_date')]);
                            });
                        });
                },
                'records' => function ($q) {
                    $q->where('concession', '>', 0);
                },
                'records.head',
            ])
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
                    ->leftJoin('options as fee_concession_types', function ($join) {
                        $join->on('students.fee_concession_type_id', '=', 'fee_concession_types.id');
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
            ->leftJoin('fee_concessions', 'student_fees.fee_concession_id', '=', 'fee_concessions.id')
            ->whereHas('records', function ($q) {
                $q->select(\DB::raw('sum(concession) as total_concession'))
                    ->having('total_concession', '>', 0);
            })
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('fee_concession_type'), function ($q, $feeConcessionType) {
                $q->where('fee_concession_types.uuid', $feeConcessionType);
            })
            ->when($request->query('fee_concession'), function ($q, $concessionUuid) {
                $q->where('fee_concessions.uuid', '=', $concessionUuid);
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
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
                    ->leftJoin('options as fee_concession_types', function ($join) {
                        $join->on('students.fee_concession_type_id', '=', 'fee_concession_types.id');
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
            ->leftJoin('fee_concessions', 'student_fees.fee_concession_id', '=', 'fee_concessions.id')
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($request->query('fee_concession_type'), function ($q, $feeConcessionType) {
                $q->where('fee_concession_types.uuid', $feeConcessionType);
            })
            ->when($request->query('fee_concession'), function ($q, $concessionUuid) {
                $q->where('fee_concessions.uuid', '=', $concessionUuid);
            })
            ->whereIn('student_fees.student_id', $studentIds)
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ]);

        $summary = \DB::query()
            ->fromSub($summary->addSelect([
                'student_fees.id',
                \DB::raw('(SELECT SUM(concession) FROM student_fee_records WHERE student_fee_id = student_fees.id) as fee_concession'),
                \DB::raw('(SELECT SUM(sfp.concession_amount)
                    FROM student_fee_payments sfp
                    JOIN transactions t ON t.id = sfp.transaction_id
                    WHERE sfp.student_fee_id = student_fees.id
                    AND t.cancelled_at IS NULL
                    AND t.rejected_at IS NULL
                    AND (t.is_online = 0 OR (t.is_online = 1 AND t.processed_at IS NOT NULL))
                    '.($request->query('start_date') && $request->query('end_date') ?
                        'AND t.date BETWEEN "'.$request->query('start_date').'" AND "'.$request->query('end_date').'"'
                        : '').'
                ) as payment_concession'),
            ]), 'derived')
            ->selectRaw('SUM(fee_concession) as concession_fee, SUM(payment_concession) as concession_amount')
            ->first();

        return FeeConcessionListResource::collection($this->filter($request)
            ->whereIn('student_id', $studentIds)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Fee Concession Report',
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
                    ['key' => 'concession', 'label' => ''],
                    ['key' => 'detail', 'label' => \Price::from($summary->concession_amount)->formatted.' / '.\Price::from($summary->concession_fee)->formatted, 'align' => 'center'],
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
