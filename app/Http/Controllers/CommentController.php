<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __invoke(Request $request, CommentService $service)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = $service->post($request);

        $comment->load('user');

        return response()->success([
            'message' => trans('global.posted', ['attribute' => trans('comment.comment')]),
            'comment' => CommentResource::make($comment),
        ]);
    }
}
