<?php

namespace App\Services\Exam;

use App\Contracts\ListGenerator;
use App\Http\Resources\Exam\OnlineExamQuestionResource;
use App\Models\Exam\OnlineExam;
use App\Models\Exam\OnlineExamQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OnlineExamQuestionListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'title', 'mark', 'position'];

    protected $defaultSort = 'position';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('exam.online_exam.question.props.title'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'type',
                'label' => trans('exam.online_exam.question.props.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'mark',
                'label' => trans('exam.online_exam.question.props.mark'),
                'print_label' => 'mark',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'created_at',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request, OnlineExam $onlineExam): Builder
    {
        return OnlineExamQuestion::query()
            ->whereOnlineExamId($onlineExam->id)
            ->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request, OnlineExam $onlineExam): AnonymousResourceCollection
    {
        if ($request->query('all')) {
            return OnlineExamQuestionResource::collection($this->filter($request, $onlineExam)
                ->orderBy('position', 'asc')
                ->get());
        }

        return OnlineExamQuestionResource::collection($this->filter($request, $onlineExam)
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

    public function list(Request $request, OnlineExam $onlineExam): AnonymousResourceCollection
    {
        return $this->paginate($request, $onlineExam);
    }
}
