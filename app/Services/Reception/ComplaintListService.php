<?php

namespace App\Services\Reception;

use App\Contracts\ListGenerator;
use App\Http\Resources\Reception\ComplaintResource;
use App\Models\Reception\Complaint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ComplaintListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'codeNumber',
                'label' => trans('reception.complaint.props.code_number'),
                'print_label' => 'code_number',
                'print_sub_label' => 'status.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'student',
                'label' => trans('student.student'),
                'print_label' => 'student.name',
                'print_sub_label' => 'student.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'student.course_name',
                'print_sub_label' => 'student.batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('reception.complaint.props.type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'subject',
                'label' => trans('reception.complaint.props.subject'),
                'print_label' => 'subject_excerpt',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'complainant',
                'label' => trans('reception.complaint.props.complainant'),
                'print_label' => 'complainant_name',
                'print_sub_label' => 'complainant_contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('reception.complaint.props.date'),
                'print_label' => 'date.formatted',
                'print_sub_label' => 'resolved_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'assignedTo',
                'label' => trans('reception.complaint.props.assigned_to'),
                'print_label' => 'incharges',
                'print_key' => 'employee.name',
                'type' => 'array',
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
        $types = Str::toArray($request->query('types'));

        return Complaint::query()
            ->byTeam()
            ->filterAccessible()
            // ->withFirstIncharge()
            ->with(['model' => fn ($q) => $q->summary(), 'type', 'incharges', 'incharges.employee' => fn ($q) => $q->summary()])
            ->when($types, function ($q, $types) {
                $q->whereHas('type', function ($q) use ($types) {
                    $q->whereIn('uuid', $types);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:code_number',
                'App\QueryFilters\LikeMatch:subject',
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ComplaintResource::collection($this->filter($request)
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
