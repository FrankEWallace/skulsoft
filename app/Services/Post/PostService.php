<?php

namespace App\Services\Post;

use App\Concerns\HasStorage;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Post\Post;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostService
{
    use HasStorage;

    public function preRequisite(Request $request)
    {
        return [];
    }

    public function getAuthor(Request $request, Post $post)
    {
        $userIds = [$post->user_id];

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
    }

    public function create(Request $request): Post
    {
        \DB::beginTransaction();

        $post = Post::forceCreate($this->formatParams($request));

        $this->addImages($request, $post);

        \DB::commit();

        $post->refresh();

        return $post;
    }

    private function addImages(Request $request, Post $post): void
    {
        $images = [];
        foreach ($request->images as $image) {
            $image = Arr::get($image, 'url');
            $newPath = 'post/images/'.basename($image);
            $this->moveFile(
                path: $image,
                newPath: $newPath,
                visibility: 'public',
            );

            $thumbFilename = Str::of($image)->replaceLast('.', '-thumb.');
            $newThumbPath = 'post/images/'.basename($thumbFilename);

            $this->moveFile(
                path: $thumbFilename,
                newPath: $newThumbPath,
                visibility: 'public',
            );

            $images[] = [
                'url' => $newPath,
            ];
        }

        $post->setMeta([
            'images' => $images,
        ]);
        $post->save();
    }

    private function formatParams(Request $request, ?Post $post = null): array
    {
        $formatted = [
            'content' => $request->content,
            'visibility' => $request->visibility,
        ];

        if (! $post) {
            $formatted['user_id'] = auth()->id();
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        $meta = $post?->meta ?? [];

        $formatted['meta'] = $meta;

        return $formatted;
    }

    private function updateImages(Request $request, Post $post): void
    {
        $existingImages = collect($post->getMeta('images', []))
            ->pluck('url')
            ->toArray();

        $images = [];
        foreach ($request->images as $image) {
            $image = Arr::get($image, 'url');

            if (in_array($image, $existingImages)) {
                $images[] = [
                    'url' => $image,
                ];

                continue;
            }

            $newPath = 'post/images/'.basename($image);
            $this->moveFile(
                path: $image,
                newPath: $newPath,
                visibility: 'public',
            );

            $thumbFilename = Str::of($image)->replaceLast('.', '-thumb.');
            $newThumbPath = 'post/images/'.basename($thumbFilename);

            $this->moveFile(
                path: $thumbFilename,
                newPath: $newThumbPath,
                visibility: 'public',
            );

            $images[] = [
                'url' => $newPath,
            ];
        }

        $newImages = collect($images)
            ->pluck('url')
            ->toArray();

        foreach ($existingImages as $image) {
            $thumbPath = Str::of($image)->replaceLast('.', '-thumb.');

            if (in_array($image, $newImages)) {
                continue;
            }

            $this->deleteImageFile(
                visibility: 'public',
                path: $image,
            );

            $this->deleteImageFile(
                visibility: 'public',
                path: $thumbPath,
            );
        }

        $post->setMeta([
            'images' => $images,
        ]);
        $post->save();
    }

    public function update(Request $request, Post $post): void
    {
        if (! $post->is_editable) {
            throw ValidationException::withMessages([
                'message' => trans('user.errors.permission_denied'),
            ]);
        }

        \DB::beginTransaction();

        $post->forceFill($this->formatParams($request, $post))->save();

        $this->updateImages($request, $post);

        \DB::commit();
    }

    public function deletable(Post $post): bool
    {
        if (! $post->is_editable) {
            throw ValidationException::withMessages([
                'message' => trans('user.errors.permission_denied'),
            ]);
        }

        return true;
    }

    public function delete(Post $post): void
    {
        $images = $post->getMeta('images', []);

        foreach ($images as $image) {
            $image = Arr::get($image, 'url');

            $this->deleteImageFile(
                visibility: 'public',
                path: $image,
            );

            $thumbFilename = Str::of($image)->replaceLast('.', '-thumb.');

            $this->deleteImageFile(
                visibility: 'public',
                path: $thumbFilename,
            );
        }

        $post->delete();
    }
}
