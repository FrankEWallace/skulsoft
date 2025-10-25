<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\TransferResource;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransferListService extends ListGenerator
{
    protected $allowedSorts = ['leaving_date'];

    protected $defaultSort = 'leaving_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('student.admission.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'transfer_certificate_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'contact_number',
                'sortable' => true,
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
                'key' => 'admissionDate',
                'label' => trans('student.admission.props.date'),
                'print_label' => 'joining_date.formatted',
                'sortable' => false,
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
                'key' => 'transferDate',
                'label' => trans('student.transfer.props.date'),
                'print_label' => 'leaving_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'reason',
                'label' => trans('student.transfer.props.reason'),
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
        return Student::query()
            ->byPeriod()
            ->filterTransferred()
            ->filter([
                'App\QueryFilters\LikeMatch:first_name',
                'App\QueryFilters\LikeMatch:last_name',
                'App\QueryFilters\LikeMatch:father_name',
                'App\QueryFilters\LikeMatch:mother_name',
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:options.uuid,reasons',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
                'App\QueryFilters\DateBetween:admission_start_date,admission_end_date,joining_date',
                'App\QueryFilters\DateBetween:transfer_start_date,transfer_end_date,leaving_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return TransferResource::collection($this->filter($request)
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

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
