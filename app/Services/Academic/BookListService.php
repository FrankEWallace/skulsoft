<?php

namespace App\Services\Academic;

use App\Enums\Academic\BookListType;
use App\Http\Resources\Academic\CourseResource;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Academic\BookList;
use App\Models\Academic\Course;
use App\Models\Academic\Subject;
use Illuminate\Http\Request;

class BookListService
{
    public function preRequisite(): array
    {
        $types = BookListType::getOptions();

        $courses = CourseResource::collection(Course::query()
            ->byPeriod()
            ->get());

        $subjects = SubjectResource::collection(Subject::query()
            ->byPeriod()
            ->get());

        return compact('types', 'courses', 'subjects');
    }

    public function create(Request $request): BookList
    {
        \DB::beginTransaction();

        $bookList = BookList::forceCreate($this->formatParams($request));

        \DB::commit();

        return $bookList;
    }

    private function formatParams(Request $request, ?BookList $bookList = null): array
    {
        $formatted = [
            'course_id' => $request->course_id,
            'subject_id' => $request->subject_id,
            'type' => $request->type,
            'title' => $request->title,
            'author' => $request->author,
            'publisher' => $request->publisher,
            'quantity' => empty($request->quantity) ? 1 : $request->quantity,
            'pages' => empty($request->pages) ? null : $request->pages,
            'description' => $request->description,
        ];

        if (! $bookList) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, BookList $bookList): void
    {
        \DB::beginTransaction();

        $bookList->forceFill($this->formatParams($request, $bookList))->save();

        \DB::commit();
    }

    public function deletable(BookList $bookList, $validate = false): bool
    {
        return true;
    }
}
