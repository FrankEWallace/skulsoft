<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Models\Post\Post;
use App\Services\Post\PostImageService;
use Illuminate\Http\Request;

class PostImageController extends Controller
{
    public function store(Request $request, PostImageService $service)
    {
        $this->authorize('create', Post::class);

        $image = $service->upload($request);

        return response()->success(['image' => $image]);
    }

    public function destroy(Request $request, PostImageService $service)
    {
        $this->authorize('create', Post::class);

        $service->deleteImage($request);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('post.props.image')]),
        ]);
    }
}
