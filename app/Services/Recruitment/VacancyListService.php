<?php

namespace App\Services\Recruitment;

use App\Contracts\ListGenerator;
use App\Http\Resources\Recruitment\VacancyResource;
use App\Models\Recruitment\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VacancyListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'last_application_date'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('recruitment.vacancy.props.code_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => trans('recruitment.vacancy.props.title'),
                'print_label' => 'title_excerpt',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'records',
                'label' => trans('employee.designation.designation'),
                'type' => 'array',
                'print_label' => 'records',
                'print_key' => 'designation.name',
                'print_sub_key' => 'number_of_positions',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'lastApplicationDate',
                'label' => trans('recruitment.vacancy.props.last_application_date'),
                'print_label' => 'last_application_date.formatted',
                'sortable' => true,
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
        return Vacancy::query()
            ->with('records.designation', 'records.employmentType')
            ->byTeam()
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return VacancyResource::collection($this->filter($request)
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
