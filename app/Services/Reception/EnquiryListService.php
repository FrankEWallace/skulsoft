<?php

namespace App\Services\Reception;

use App\Contracts\ListGenerator;
use App\Http\Resources\Reception\EnquiryResource;
use App\Models\Reception\Enquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class EnquiryListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'code_number', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('reception.enquiry.props.code_number'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'nature',
                'label' => trans('reception.enquiry.props.nature'),
                'print_label' => 'nature.label',
                'sortable' => false,
                'visibility' => false,
            ],
            [
                'key' => 'date',
                'label' => trans('reception.enquiry.props.date'),
                'print_label' => 'date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'name',
                'label' => trans('reception.enquiry.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'stage',
                'label' => trans('reception.enquiry.stage.stage'),
                'print_label' => 'stage.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('reception.enquiry.type.type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'source',
                'label' => trans('reception.enquiry.source.source'),
                'print_label' => 'source.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'employee',
                'label' => trans('reception.enquiry.props.assigned_to'),
                'print_label' => 'employee.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'status',
                'label' => trans('reception.enquiry.props.status'),
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
            array_unshift($headers, ['key' => 'selectAll', 'sortable' => false]);
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $categories = Str::toArray($request->query('categories'));
        $types = Str::toArray($request->query('types'));
        $sources = Str::toArray($request->query('sources'));
        $stages = Str::toArray($request->query('stages'));
        $courses = Str::toArray($request->query('courses'));
        $employees = Str::toArray($request->query('employees'));

        return Enquiry::query()
            ->byPeriod()
            ->filterAccessible()
            ->with(['type', 'source', 'stage', 'contact', 'course', 'employee' => fn ($q) => $q->summary()])
            ->when($request->nature, function ($q, $nature) {
                $q->where('nature', $nature);
            })
            ->when($types, function ($q, $types) {
                $q->whereHas('type', function ($q) use ($types) {
                    $q->whereIn('uuid', $types);
                });
            })
            ->when($sources, function ($q, $sources) {
                $q->whereHas('source', function ($q) use ($sources) {
                    $q->whereIn('uuid', $sources);
                });
            })
            ->when($stages, function ($q, $stages) {
                $q->whereHas('stage', function ($q) use ($stages) {
                    $q->whereIn('uuid', $stages);
                });
            })
            ->when($employees, function ($q, $employees) {
                $q->whereHas('employee', function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->when($courses, function ($q, $courses) {
                $q->whereHas('course', function ($q) use ($courses) {
                    $q->whereIn('uuid', $courses);
                });
            })
            ->when($request->name, function ($q, $name) {
                $q->where(function ($q) use ($name) {
                    $q->where('name', 'like', '%'.$name.'%')
                        ->orWhereHas('contact', function ($q) use ($name) {
                            $q->where('name', 'like', '%'.$name.'%');
                        });
                });
            })
            ->when($request->contact_number, function ($q, $contactNumber) {
                $q->where(function ($q) use ($contactNumber) {
                    $q->where('contact_number', '=', $contactNumber)
                        ->orWhereHas('contact', function ($q) use ($contactNumber) {
                            $q->where('contact_number', '=', $contactNumber);
                        });
                });
            })
            ->when($request->address, function ($q, $address) {
                $q->whereHas('contact', function ($q) use ($address) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.address_line1')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.address_line2')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.city')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.state')) LIKE ?", ['%'.$address.'%'])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.present.country')) LIKE ?", ['%'.$address.'%']);
                });
            })
            ->when($request->gender, function ($q, $gender) {
                $q->whereHas('contact', function ($q) use ($gender) {
                    $q->where('gender', $gender);
                });
            })
            ->when($categories, function ($q, $categories) {
                $q->whereHas('contact', function ($q) use ($categories) {
                    $q->whereHas('category', function ($q) use ($categories) {
                        $q->whereIn('uuid', $categories);
                    });
                });
            })
            ->when($request->query('previous_institute'), function ($q, $previousInstitute) {
                $q->whereHas('contact', function ($q) use ($previousInstitute) {
                    $q->whereHas('qualifications', function ($q) use ($previousInstitute) {
                        $q->where('institute', 'like', "%{$previousInstitute}%");
                    });
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
                'App\QueryFilters\ExactMatch:status',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return EnquiryResource::collection($this->filter($request)
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

    public function getIds(Request $request): array
    {
        return $this->filter($request)->select('uuid')->get()->pluck('uuid')->all();
    }
}
