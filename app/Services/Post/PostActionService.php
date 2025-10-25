<?php

namespace App\Services\Post;

use App\Models\Post\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostActionService
{
    public function pin(Request $request, Post $post): void
    {
        if (! empty($post->pinned_at->value)) {
            throw ValidationException::withMessages([
                'message' => trans('general.errors.invalid_action'),
            ]);
        }

        Post::query()
            ->byTeam()
            ->update([
                'pinned_at' => null,
            ]);

        $post->pinned_at = now()->toDateTimeString();
        $post->save();
    }

    public function unpin(Request $request, Post $post): void
    {
        if (empty($post->pinned_at->value)) {
            throw ValidationException::withMessages([
                'message' => trans('general.errors.invalid_action'),
            ]);
        }

        $post->pinned_at = null;
        $post->save();
    }
}
