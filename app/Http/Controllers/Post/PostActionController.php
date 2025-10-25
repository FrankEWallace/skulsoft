<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Models\Post\Post;
use App\Services\Post\PostActionService;
use Illuminate\Http\Request;

class PostActionController extends Controller
{
    public function pin(Request $request, string $post, PostActionService $service)
    {
        $post = Post::findByUuidOrFail($post);

        $this->authorize('update', $post);

        $service->pin($request, $post);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('post.post')]),
        ]);
    }

    public function unpin(Request $request, string $post, PostActionService $service)
    {
        $post = Post::findByUuidOrFail($post);

        $this->authorize('update', $post);

        $service->unpin($request, $post);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('post.post')]),
        ]);
    }
}
