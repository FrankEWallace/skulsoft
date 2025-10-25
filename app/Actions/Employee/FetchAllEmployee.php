<?php

namespace App\Actions\Employee;

use App\Contracts\PaginationHelper;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FetchAllEmployee extends PaginationHelper
{
    public function execute(Request $request, bool $array = false)
    {
        $date = $request->date ?? today()->toDateString();
        $paginate = false;

        if (empty($request->date)) {
            $request->merge([
                'date' => today()->toDateString(),
            ]);
        }

        if (! $request->has('paginate') || $request->boolean('paginate') == true) {
            $paginate = true;
        }

        $query = Employee::query()
            ->detail($date)
            ->filterAccessible()
            ->when($request->departments, function ($q) use ($request) {
                $q->whereIn('departments.uuid', Str::toArray($request->departments));
            })
            ->when($request->designations, function ($q) use ($request) {
                $q->whereIn('designations.uuid', Str::toArray($request->designations));
            })
            ->when($request->employmentStatuses, function ($q) use ($request) {
                $q->whereIn('options.uuid', Str::toArray($request->employmentStatuses));
            })
            ->when($request->employees, function ($q) use ($request) {
                $q->whereIn('employees.uuid', Str::toArray($request->employees));
            })
            ->filterByStatus($request->status)
            // ->when($request->status, function ($q) use ($request) {
            //     $q->where(function ($q) use ($request) {
            //         $q->whereNull('leaving_date')
            //             ->orWhere(function ($q) use ($request) {
            //                 $q->whereNotNull('leaving_date')
            //                     ->where('leaving_date', '>=', $request->date);
            //             });
            //     });
            // })
            ->filter([
                'App\QueryFilters\ExactMatch:code_number',
                'App\QueryFilters\WhereInMatch:departments.uuid,department',
                'App\QueryFilters\WhereInMatch:designations.uuid,designation',
                'App\QueryFilters\WhereInMatch:branches.uuid,branch',
                'App\QueryFilters\WhereInMatch:options.uuid,employment_status',
            ])
            ->orderBy('name', 'asc');

        if ($paginate) {
            $employees = $query->paginate($this->getPageLength(), ['*'], 'current_page');
        } else {
            $employees = $query->get();
        }

        return $array ? $employees->toArray() : $employees;
    }
}
