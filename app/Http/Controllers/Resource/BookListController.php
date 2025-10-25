<?php

namespace App\Http\Controllers\Resource;

use App\Enums\Academic\BookListType;
use App\Http\Controllers\Controller;
use App\Models\Academic\BookList;
use App\Models\Academic\Course;
use App\Models\Team;
use App\Services\Resource\BookListService;
use Illuminate\Http\Request;

class BookListController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:book-list:read');
    }

    public function preRequisite(Request $request, BookListService $service)
    {
        return $service->preRequisite($request);
    }

    public function download(Request $request, string $courseUuid)
    {
        $team = Team::query()
            ->where('id', auth()->user()->current_team_id)
            ->first();

        $course = Course::query()
            ->byPeriod()
            // ->filterAccessible()
            ->where('uuid', $courseUuid)
            ->firstOrFail();

        $bookLists = BookList::query()
            ->whereCourseId($course->id)
            ->with('subject')
            ->get();

        if (! $bookLists->count()) {
            abort(404);
        }

        $textbooks = $bookLists->where('type', '!=', BookListType::NOTEBOOK->value);

        $notebooks = $bookLists->where('type', BookListType::NOTEBOOK->value);

        $content = view()->first([config('config.print.custom_path').'academic.book-lists', 'print.academic.book-lists'], compact('textbooks', 'notebooks', 'course', 'team'))->render();

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
