<?php

namespace App\Services\Academic;

use App\Contracts\ListGenerator;
use App\Http\Resources\Academic\BookListResource;
use App\Models\Academic\BookList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BookListListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'title', 'author'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'type',
                'label' => trans('academic.book_list.props.type'),
                'print_label' => 'type.label',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course.name_with_term',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'subject',
                'label' => trans('academic.subject.subject'),
                'print_label' => 'subject.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'title',
                'label' => trans('academic.book_list.props.title'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'publisher',
                'label' => trans('academic.book_list.props.publisher'),
                'print_label' => 'publisher',
                'print_sub_label' => 'author',
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
        $courses = Str::toArray($request->query('courses'));
        $subjects = Str::toArray($request->query('subjects'));

        return BookList::query()
            ->with('course', 'subject')
            ->select('book_lists.*', 'courses.position as course_position')
            ->join('courses', 'book_lists.course_id', '=', 'courses.id')
            ->byPeriod()
            ->when($courses, function ($q, $courses) {
                $q->whereHas('course', function ($q) use ($courses) {
                    $q->whereIn('courses.uuid', $courses);
                });
            })
            ->when($subjects, function ($q, $subjects) {
                $q->whereHas('subject', function ($q) use ($subjects) {
                    $q->whereIn('subjects.uuid', $subjects);
                });
            })
            ->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\LikeMatch:author',
                'App\QueryFilters\ExactMatch:type',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        $query = $this->filter($request);

        if (! $request->query('sort')) {
            $query->orderBy('courses.position', 'asc')
                ->orderBy('type', 'asc');
        } else {
            $query->orderBy($this->getSort(), $this->getOrder());
        }

        return BookListResource::collection($query
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
