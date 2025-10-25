<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\FeeRecordResource;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomFeeListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'feeHead',
                'label' => trans('finance.fee_head.fee_head'),
                'print_label' => 'head.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'amount',
                'label' => trans('student.fee.props.amount'),
                'print_label' => 'amount.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'paid',
                'label' => trans('finance.fee.paid'),
                'print_label' => 'paid.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'dueDate',
                'label' => trans('student.fee.props.date'),
                'print_label' => 'due_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, Student $student): Builder
    {
        return FeeRecord::query()
            ->with('head')
            ->whereHas('fee', function ($q) use ($student) {
                $q->where('student_id', $student->id)
                    ->whereHas('installment', function ($q) {
                        $q->where('meta->is_custom', true);
                    });
            })
            ->filter([
                //
            ]);
    }

    public function paginate(Request $request, Student $student): AnonymousResourceCollection
    {
        return FeeRecordResource::collection($this->filter($request, $student)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request, Student $student): AnonymousResourceCollection
    {
        return $this->paginate($request, $student);
    }
}
