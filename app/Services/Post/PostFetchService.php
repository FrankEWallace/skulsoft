<?php

namespace App\Services\Post;

use App\Http\Resources\Post\PostResource;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Post\Post;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class PostFetchService
{
    public function paginate(Request $request)
    {
        $cursor = $request->cursor;

        $posts = Post::query()
            ->with('user')
            ->byTeam()
            ->when(! $cursor, function ($query) {
                $query->orderByRaw('pinned_at IS NULL')
                    ->orderByDesc('pinned_at');
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate(10);

        $userIds = $posts->pluck('user_id')->unique()->toArray();

        $contactIds = Contact::query()
            ->whereIn('user_id', $userIds)
            ->get();

        $students = Student::query()
            ->summary()
            ->whereIn('students.contact_id', $contactIds->pluck('id')->toArray())
            ->get();

        $employees = Employee::query()
            ->summary()
            ->whereIn('employees.contact_id', $contactIds->pluck('id')->toArray())
            ->get();

        $request->merge([
            'students' => $students,
            'employees' => $employees,
        ]);

        return PostResource::collection($posts);
    }
}
