<?php

namespace App\Services\Employee;

use App\Contracts\ListGenerator;
use App\Http\Resources\Employee\ProfileEditRequestResource;
use App\Models\ContactEditRequest;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileEditRequestListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'user',
                'label' => trans('employee.edit_request.request_by'),
                'print_label' => 'user.profile.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('employee.edit_request.props.status'),
                'print_label' => 'status.label',
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

    public function filter(Request $request, Employee $employee): Builder
    {
        return ContactEditRequest::query()
            ->with('user')
            ->whereModelType('Employee')
            ->whereModelId($employee->id);
    }

    public function paginate(Request $request, Employee $employee): AnonymousResourceCollection
    {
        return ProfileEditRequestResource::collection($this->filter($request, $employee)
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

    public function list(Request $request, Employee $employee): AnonymousResourceCollection
    {
        return $this->paginate($request, $employee);
    }
}
