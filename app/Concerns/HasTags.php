<?php

namespace App\Concerns;

use Illuminate\Support\Arr;

trait HasTags
{
    public function showTags(): array
    {
        if (! $this->relationLoaded('tags')) {
            return [];
        }

        $tagsDisplay = '';
        $limitedTags = 2;

        $additionalTagCount = $this->tags()->count() > $limitedTags ? ($this->tags()->count() - $limitedTags) : 0;
        $tagsDisplay = Arr::toString($this->tags()->limit($limitedTags)->get()->map(function ($tag) {
            return $tag->name;
        })->all());

        $additionalTags = '';
        if ($additionalTagCount) {
            $additionalTags = trans('global.and_others', ['attribute' => $additionalTagCount]);
            // $tagsDisplay .= (trans('global.and_others', ['attribute' => $additionalTagCount]));
        }

        return [
            'count' => $this->tags()->count(),
            'tags_display' => $tagsDisplay,
            'additional_tags' => $additionalTags,
            'additional_tag_count' => $additionalTagCount,
            'limited_tags' => $limitedTags,
        ];
    }
}
