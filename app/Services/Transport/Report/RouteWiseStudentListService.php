<?php

namespace App\Services\Transport\Report;

use App\Contracts\ListGenerator;
use App\Http\Resources\Transport\Report\RouteWiseStudentListResource;
use App\Models\Transport\RoutePassenger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RouteWiseStudentListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

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
                'print_label' => 'route.name',
                'print_sub_label' => 'route.vehicle.registration_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'stoppage',
                'label' => trans('transport.stoppage.stoppage'),
                'print_label' => 'stoppage.name',
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
        return RoutePassenger::query()
            ->with('model.contact', 'model.admission', 'model.batch.course', 'stoppage', 'route.vehicle')
            ->where('model_type', 'Student')
            ->when($request->query('route'), function ($q, $route) {
                $q->whereHas('route', function ($q) use ($route) {
                    $q->where('uuid', $route);
                });
            })
            ->when($request->query('stoppage'), function ($q, $stoppage) {
                $q->whereHas('stoppage', function ($q) use ($stoppage) {
                    $q->where('uuid', $stoppage);
                });
            });
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return RouteWiseStudentListResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'filename' => 'Route Wise Student Report',
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
