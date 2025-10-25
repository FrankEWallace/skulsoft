<?php

namespace App\Services\Recruitment;

use App\Contracts\ListGenerator;
use App\Http\Resources\Recruitment\ApplicationResource;
use App\Models\Recruitment\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('recruitment.vacancy.props.title'),
                'print_label' => 'vacancy.title_excerpt',
                'print_sub_label' => 'vacancy.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'designation',
                'label' => trans('employee.designation.designation'),
                'print_label' => 'designation.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'applicant',
                'label' => trans('recruitment.application.applicant'),
                'print_label' => 'contact.contact_number',
                'print_sub_label' => 'qualification_summary',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'contact',
                'label' => trans('contact.contact'),
                'print_label' => 'contact.contact_number',
                'print_sub_label' => 'contact.email',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'address',
                'label' => trans('contact.props.address.address'),
                'print_label' => 'contact.present_address.city',
                'print_sub_label' => 'contact.present_address.state',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'applicationDate',
                'label' => trans('recruitment.application.props.application_date'),
                'print_label' => 'application_date.formatted',
                'print_sub_label' => 'availability_date.formatted',
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

    public function filter(Request $request): Builder
    {
        return Application::query()
            ->with('vacancy', 'contact', 'designation')
            ->byTeam()
            ->filter([
                'App\QueryFilters\DateBetween:start_date,end_date,application_date',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ApplicationResource::collection($this->filter($request)
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
