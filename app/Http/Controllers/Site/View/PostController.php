<?php

namespace App\Http\Controllers\Site\View;

use App\Enums\Post\Visibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\Post\PostResource;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Post\Post;
use App\Models\Student\Student;
use App\Services\Post\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct()
    {
        // $this->middleware('feature.available:feature.enable_post');
    }

    public function index(Request $request)
    {
        $cursor = $request->cursor;

        $posts = Post::query()
            ->with('user', 'team')
            ->whereIn('visibility', [Visibility::PUBLIC])
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

    public function show(Request $request, string $uuid, PostService $service)
    {
        $post = Post::query()
            ->with('user', 'team')
            ->whereIn('visibility', [Visibility::PUBLIC])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $service->getAuthor($request, $post);

        $post->load(['user', 'comments' => function ($q) {
            $q->with('user')
                ->orderBy('created_at', 'desc')
                ->take(1);
        }]);

        $request->merge([
            'show_details' => true,
        ]);

        return PostResource::make($post);
    }
}
