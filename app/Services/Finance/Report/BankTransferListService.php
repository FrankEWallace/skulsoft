<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Finance\Report\BankTransferListResource;
use App\Models\Finance\BankTransfer;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BankTransferListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'serial_number', 'code_number', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('finance.bank_transfer.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'voucher_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('student.props.name'),
                'print_label' => 'details.name',
                'print_sub_label' => 'details.code_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'fatherName',
                'label' => trans('contact.props.father_name'),
                'print_label' => 'details.father_name',
                'print_sub_label' => 'details.contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'details.courseName + details.batchName',
                // 'print_sub_label' => 'batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'amount',
                'label' => trans('finance.bank_transfer.props.amount'),
                'type' => 'currency',
                'print_label' => 'amount.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('finance.bank_transfer.props.date'),
                'type' => 'date',
                'print_label' => 'date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('finance.bank_transfer.props.status'),
                'print_label' => 'status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'requester',
                'label' => trans('user.user'),
                'print_label' => 'requester.profile.name',
                'sortable' => false,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        return BankTransfer::query()
            ->with('requester', 'approver')
            ->byTeam()
            ->filter([
                'App\QueryFilters\LikeMatch:code_number,bank_transfers.code_number',
                'App\QueryFilters\DateBetween:start_date,end_date,bank_transfers.date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $bankTransfers = $this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page');

        $transactions = Transaction::query()
            ->whereIn('meta->bank_transfer_id', $bankTransfers->pluck('id'))
            ->get();

        $students = Student::query()
            ->summary()
            ->whereIn('students.id', $bankTransfers->filter(function ($bankTransfer) {
                return $bankTransfer->model_type == 'Student';
            })->pluck('model_id'))
            ->get();

        $request->merge([
            'students' => $students,
            'transactions' => $transactions,
        ]);

        return BankTransferListResource::collection($bankTransfers)
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Bank Transfer Report',
                    'sno' => $this->getSno(),
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
