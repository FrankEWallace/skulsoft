<?php

namespace App\Http\Controllers\Site;

use App\Enums\Academic\BookListType;
use App\Models\Academic\BookList;
use App\Models\Academic\Course;
use App\Models\Team;

class BookListController
{
    public function __invoke($courseUuid)
    {
        $team = Team::query()
            ->first();

        config([
            'config.team' => $team,
        ]);

        $course = Course::query()
            ->where('uuid', $courseUuid)
            ->firstOrFail();

        $bookLists = BookList::query()
            ->whereCourseId($course->id)
            ->with('subject')
            ->get();

        $textbooks = $bookLists->where('type', '!=', BookListType::NOTEBOOK->value);

        $notebooks = $bookLists->where('type', BookListType::NOTEBOOK->value);

        $content = view()->first([config('config.print.custom_path').'academic.book-lists', 'print.academic.book-lists'], compact('textbooks', 'notebooks', 'course'))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
        ]);
        // to support unicode characters
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($content);
        $mpdf->Output();

    }
}
