<?php

namespace App\Http\Resources\Post;

use App\Concerns\HasStorage;
use App\Enums\Post\Visibility;
use App\Http\Resources\CommentResource;
use App\Http\Resources\UserSummaryForGuestResource;
use App\Http\Resources\UserSummaryResource;
use App\Support\MarkdownParser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PostResource extends JsonResource
{
    use HasStorage, MarkdownParser;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $imagesCount = count($this->getMeta('images', []));

        $content = $this->getContent($request);

        return [
            'uuid' => $this->uuid,
            'content_original' => $this->content,
            'content' => Arr::get($content, 'content'),
            'has_more' => (bool) Arr::get($content, 'has_more'),
            'author' => $this->getAuthor($request),
            $this->mergeWhen(auth()->check(), [
                'user' => UserSummaryResource::make($this->whenLoaded('user')),
            ], [
                'user' => UserSummaryForGuestResource::make($this->whenLoaded('user')),
            ]),
            // $this->mergeWhen($this->relationLoaded('team'), [
            //     'uuid' => $this->team->uuid,
            //     'name' => $this->team->name,
            //     'code' => $this->team->code,
            // ]),
            'team' => [
                'uuid' => $this->team->uuid,
                'name' => $this->team->name,
                'code' => $this->team->code,
            ],
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'is_editable' => $this->is_editable,
            'images' => $this->getImages(),
            'images_count' => $imagesCount,
            'and_more_count' => $imagesCount > 4 ? $imagesCount - 4 : 0,
            'pinned_at' => $this->pinned_at,
            'is_pinned' => ! empty($this->pinned_at->value),
            'visibility' => Visibility::getDetail($this->visibility->value ?? 'public'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getAuthor($request)
    {
        if (empty($request->employees) && empty($request->students)) {
            return [
                'name' => $this->user->name,
                'detail' => $this->user->hasRole('admin') ? trans('team.config.role.admin') : null,
            ];
        }

        $author = $request->employees?->firstWhere('user_id', $this->user_id);

        if (! $author) {
            $author = $request->students?->firstWhere('user_id', $this->user_id);

            if ($author) {
                $name = $author->name;
                $detail = $author->course_name.' '.$author->batch_name;
            } else {
                $name = $this->user->name;
                $detail = trans('team.config.role.admin');
            }
        } else {
            $name = $author->name;
            $detail = $author->designation_name;
        }

        return [
            'name' => $name,
            'detail' => $detail,
        ];
    }

    private function getContent(Request $request)
    {
        if ($request->show_details) {
            return [
                'content' => $this->parse($this->content),
                'has_more' => false,
            ];
        }

        $truncated = $this->truncateToFullSentence($this->content, 200);

        $isTruncated = trim($truncated) !== trim($this->content);

        return [
            'content' => $this->parse($truncated),
            'has_more' => $isTruncated,
        ];
    }

    private function truncateToFullSentence($text, $maxWords = 200)
    {
        if (str_word_count($text) <= $maxWords) {
            return $text;
        }

        // Match sentences including their trailing whitespace (including newlines)
        preg_match_all('/.*?[.?!](\s+|$)/s', $text, $matches);

        $sentences = $matches[0];
        $wordCount = 0;
        $result = '';

        foreach ($sentences as $sentence) {
            $sentenceWordCount = str_word_count($sentence);
            if ($wordCount + $sentenceWordCount > $maxWords) {
                break;
            }
            $result .= $sentence; // preserve original whitespace/newlines
            $wordCount += $sentenceWordCount;
        }

        return $result;
    }

    private function getImages()
    {
        return collect($this->meta['images'] ?? [])->map(function ($image) {
            $image = Arr::get($image, 'url');
            $path = Str::of($image)->replaceLast('.', '-thumb.');

            return [
                'uuid' => (string) Str::uuid(),
                'url' => $this->getImageFile(visibility: 'public', path: $image),
                'path' => $image,
                'thumbnail' => $this->getImageFile(visibility: 'public', path: $path),
            ];
        })->toArray();
    }
}
