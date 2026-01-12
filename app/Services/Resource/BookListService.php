<?php

namespace App\Services\Resource;

use App\Http\Resources\Academic\CourseResource;
use App\Domain\Academic\Models\Course;
use Illuminate\Http\Request;

class BookListService
{
    public function preRequisite(Request $request): array
    {
        $courses = CourseResource::collection(Course::query()
            ->byPeriod()
            // ->filterAccessible()
            ->get());

        return compact('courses');
    }
}
