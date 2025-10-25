<?php

namespace App\Services\Transport\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\Report\BatchWiseRouteListResource;
use App\Models\Student\Student;
use App\Models\Transport\RoutePassenger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BatchWiseRouteListService extends ListGenerator
{
    protected $allowedSorts = ['code_number', 'name'];

    protected $defaultSort = 'name';

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
                'key' => 'route',
                'label' => trans('transport.route.route'),
                'type' => 'array',
                'print_label' => 'passengers',
                'print_key' => 'route_name',
                'print_sub_key' => 'stoppage_name',
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
        return Student::query()
            ->summary()
            ->byPeriod()
            ->filterAccessible()
            ->when($request->query('name'), function ($q, $name) {
                $q->where(\DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ")'), 'like', "%{$name}%");
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\WhereInMatch:batches.uuid,batches',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $batches = Str::toArray($request->query('batches'));

        $pageLength = (int) $this->getPageLength();
        $currentPage = (int) $request->query('current_page', 1);

        $students = Student::query()
            ->select('id')
            ->when($batches, function ($q) use ($batches) {
                $q->whereHas('batch', function ($q) use ($batches) {
                    $q->whereIn('uuid', $batches);
                });
            })
            ->skip($pageLength * ($currentPage - 1))
            ->limit($pageLength)
            ->get();

        $transportRoutePassengers = RoutePassenger::query()
            ->select('model_id', 'transport_routes.name as route_name', 'transport_stoppages.name as stoppage_name', 'transport_routes.direction')
            ->join('transport_routes', 'transport_routes.id', '=', 'transport_route_passengers.route_id')
            ->join('transport_stoppages', 'transport_stoppages.id', '=', 'transport_route_passengers.stoppage_id')
            ->where('model_type', 'Student')
            ->whereIn('model_id', $students->pluck('id'))
            ->get();

        $request->merge([
            'passengers' => $transportRoutePassengers,
        ]);

        return BatchWiseRouteListResource::collection($this->filter($request)
            ->groupBy('students.id')
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Batch Wise Transport Route Report',
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
