<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\DocumentResource;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('student.document.props.title'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('student.document_type.document_type'),
                'print_label' => 'type.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'startDate',
                'label' => trans('student.document.props.start_date'),
                'print_label' => 'start_date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'endDate',
                'label' => trans('student.document.props.end_date'),
                'print_label' => 'end_date.formatted',
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

    public function filter(Request $request, Student $student): Builder
    {
        return Document::query()
            ->with('type')
            ->whereHasMorph(
                'documentable', [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\DateBetween:start_date,end_date,start_date,end_date',
            ]);
    }

    public function paginate(Request $request, Student $student): AnonymousResourceCollection
    {
        return DocumentResource::collection($this->filter($request, $student)
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

    public function list(Request $request, Student $student): AnonymousResourceCollection
    {
        return $this->paginate($request, $student);
    }
}
