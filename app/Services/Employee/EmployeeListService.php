<?php

namespace App\Services\Employee;

use App\Contracts\ListGenerator;
use App\Enums\Employee\Type;
use App\Http\Resources\Employee\EmployeeBasicResource;
use App\Http\Resources\Employee\EmployeeListResource;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmployeeListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name', 'code_number', 'joining_date', 'employment_status', 'department', 'designation'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('employee.props.code_number'),
                'print_label' => 'code_number',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('employee.props.name'),
                'print_label' => 'name',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'joiningDate',
                'label' => trans('employee.props.joining_date'),
                'print_label' => 'joining_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'employmentStatus',
                'label' => trans('employee.employment_status.employment_status'),
                'print_label' => 'employment_status',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'department',
                'label' => trans('employee.department.department'),
                'print_label' => 'department',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'designation',
                'label' => trans('employee.designation.designation'),
                'print_label' => 'designation',
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
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $search = $request->query('search');
        $basic = $request->boolean('basic');
        $status = $request->query('status', 'active');
        $types = Str::toArray($request->query('types'));

        if (empty($types)) {
            $types = explode(',', config('config.employee.default_employee_types'));
        } else if ($types == 'all') {
            $types = Type::getKeys();
        }

        if ($request->query('type') == 'all') {
            $types = [];
        }

        $date = today()->toDateString();

        $tagsIncluded = Str::toArray($request->query('tags_included'));
        $tagsExcluded = Str::toArray($request->query('tags_excluded'));
        $groups = Str::toArray($request->query('groups'));

        $otherTeamMember = $request->boolean('other_team_member');

        return Employee::query()
            ->when($basic, function ($q) use ($date, $otherTeamMember) {
                $q->summary($date, $otherTeamMember);
            }, function ($q) use ($date, $otherTeamMember) {
                $q->detail($date, $otherTeamMember)
                    ->filterAccessible();
            })
            ->filterByStatus($status)
            ->when($types, function ($q, $types) {
                $q->whereIn('employees.type', $types);
            })
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$search}%")
                        ->orWhere('employees.code_number', 'like', "%{$search}%");
                });
            })
            ->when($tagsIncluded, function ($q, $tagsIncluded) {
                $q->whereHas('tags', function ($q) use ($tagsIncluded) {
                    $q->whereIn('name', $tagsIncluded);
                });
            })
            ->when($tagsExcluded, function ($q, $tagsExcluded) {
                $q->whereDoesntHave('tags', function ($q) use ($tagsExcluded) {
                    $q->whereIn('name', $tagsExcluded);
                });
            })
            ->when($groups, function ($q, $groups) {
                $q->leftJoin('group_members', 'employees.id', 'group_members.model_id')
                    ->where('group_members.model_type', 'Employee')
                    ->join('options as employee_groups', 'group_members.model_group_id', 'employee_groups.id')
                    ->whereIn('employee_groups.uuid', $groups);
            })
            ->filter([
                'App\QueryFilters\WhereInMatch:employees.uuid,uuid',
                'App\QueryFilters\WhereInMatch:departments.uuid,department',
                'App\QueryFilters\WhereInMatch:designations.uuid,designation',
                'App\QueryFilters\WhereInMatch:options.uuid,employment_status',
                'App\QueryFilters\ExactMatch:code_number',
                'App\QueryFilters\DateBetween:joining_start_date,joining_end_date,joining_date',
                'App\QueryFilters\DateBetween:leaving_start_date,leaving_end_date,leaving_date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $view = $request->query('view', 'card');
        $request->merge(['view' => $view]);

        $query = $this->filter($request);

        if ($this->getSort() == 'code_number') {
            $query->orderBy('code_number', $this->getOrder());
        } elseif ($this->getSort() == 'name') {
            $query->orderBy('name', $this->getOrder());
        } elseif ($this->getSort() == 'employment_status') {
            $query->orderBy('options.name', $this->getOrder());
        } elseif ($this->getSort() == 'department') {
            $query->orderBy('departments.name', $this->getOrder());
        } elseif ($this->getSort() == 'designation') {
            $query->orderBy('designations.name', $this->getOrder());
        } elseif ($this->getSort() == 'created_at') {
            $query->orderBy('employees.created_at', $this->getOrder());
        } else {
            $query->orderBy($this->getSort(), $this->getOrder());
        }

        $query->orderBy('employees.number', 'asc');

        if ($request->boolean('basic')) {
            return EmployeeBasicResource::collection($query
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

        return EmployeeListResource::collection($query
            ->when($request->query('output') == 'export_all_excel', function ($q) {
                return $q->get();
            }, function ($q) {
                return $q->paginate((int) $this->getPageLength(), ['*'], 'current_page');
            }))
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

    public function listAll(Request $request): Collection
    {
        return $this->filter($request)->get();
    }
}
