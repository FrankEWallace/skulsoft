<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\SubjectListResource;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BatchSubjectListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('academic.subject.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('academic.subject.props.code'),
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

    public function filter(Request $request, Batch $batch): Builder
    {
        return Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id);
    }

    public function paginate(Request $request, Batch $batch): AnonymousResourceCollection
    {
        $query = $this->filter($request, $batch);

        $query->orderBy('subjects.position', $this->getOrder());

        return SubjectListResource::collection($query
            // ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->paginate(100, ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request, Batch $batch): AnonymousResourceCollection
    {
        return $this->paginate($request, $batch);
    }
}
