<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\PostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post\Post;
use App\Services\Post\PostFetchService;
use App\Services\Post\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('feature.available:feature.enable_post');
    }

    public function preRequisite(Request $request, PostService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, PostFetchService $service)
    {
        return $service->paginate($request);
    }

    public function store(PostRequest $request, PostService $service)
    {
        $post = $service->create($request);

        $post->load('user');

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('post.post')]),
            'post' => PostResource::make($post),
        ]);
    }

    public function show(Request $request, string $post, PostService $service)
    {
        $post = Post::findByUuidOrFail($post);

        $service->getAuthor($request, $post);
        $post->load(['user', 'comments' => function ($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }]);

        $request->merge([
            'show_details' => true,
        ]);

        return PostResource::make($post);
    }

    public function update(PostRequest $request, string $post, PostService $service)
    {
        $post = Post::findByUuidOrFail($post);

        $service->update($request, $post);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('post.post')]),
        ]);
    }

    public function destroy(string $post, PostService $service)
    {
        $post = Post::findByUuidOrFail($post);

        $service->deletable($post);

        $service->delete($post);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('post.post')]),
        ]);
    }
}
