<?php

namespace App\Services\Student;

use App\Contracts\ListGenerator;
use App\Http\Resources\Student\DialogueResource;
use App\Models\Contact;
use App\Models\Dialogue;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DialogueListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'created_at';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'title',
                'label' => trans('student.dialogue.props.title'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'category',
                'label' => trans('student.dialogue_category.dialogue_category'),
                'print_label' => 'category.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'date',
                'label' => trans('student.dialogue.props.date'),
                'print_label' => 'date.formatted',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'user',
                'label' => trans('user.user'),
                'print_label' => 'user.name',
                'sortable' => false,
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
        return Dialogue::query()
            ->with('category', 'user')
            ->whereHasMorph(
                'model', [Contact::class],
                function ($q) use ($student) {
                    $q->whereId($student->contact_id);
                }
            )->filter([
                'App\QueryFilters\LikeMatch:title',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request, Student $student): AnonymousResourceCollection
    {
        return DialogueResource::collection($this->filter($request, $student)
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
