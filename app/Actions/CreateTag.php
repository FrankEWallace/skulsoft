<?php

namespace App\Actions;

use App\Models\Tag;
use Illuminate\Support\Str;

class CreateTag
{
    public function execute(array $tags = []): array
    {
        $tagIds = [];
        foreach ($tags as $tag) {
            $tag = Tag::firstOrCreate(['name' => Str::slug($tag)]);
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }
}
