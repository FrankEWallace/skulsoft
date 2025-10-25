<?php

namespace App\Services;

use App\Models\Comment;
use App\Support\ValidateComment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CommentService
{
    use ValidateComment;

    public function post(Request $request)
    {
        $data = $this->validateInput($request);

        $comment = Comment::create([
            'commentable_type' => Arr::get($data, 'commentable_type'),
            'commentable_id' => Arr::get($data, 'commentable_id'),
            'body' => $request->body,
            'user_id' => auth()->id(),
        ]);

        return $comment;
    }
}
