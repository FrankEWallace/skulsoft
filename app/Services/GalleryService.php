<?php

namespace App\Services;

use App\Concerns\HasStorage;
use App\Enums\GalleryType;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GalleryService
{
    use HasStorage;

    public function preRequisite(Request $request): array
    {
        $types = GalleryType::getOptions();

        return compact('types');
    }

    public function create(Request $request): Gallery
    {
        \DB::beginTransaction();

        $gallery = Gallery::forceCreate($this->formatParams($request));

        \DB::commit();

        return $gallery;
    }

    private function formatParams(Request $request, ?Gallery $gallery = null): array
    {
        $formatted = [
            'type' => $request->type,
            'title' => $request->title,
            'date' => $request->date,
            'description' => $request->description,
        ];

        if (! $gallery) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Gallery $gallery): void
    {
        \DB::beginTransaction();

        $gallery->forceFill($this->formatParams($request, $gallery))->save();

        \DB::commit();
    }

    public function deletable(Gallery $gallery): void {}

    public function delete(Gallery $gallery): void
    {
        foreach ($gallery->images as $galleryImage) {
            $this->deleteImageFile(
                visibility: 'public',
                path: $galleryImage->path,
            );

            $this->deleteImageFile(
                visibility: 'public',
                path: Str::of($galleryImage->path)->replaceLast('.', '-thumb.'),
            );
        }

        $gallery->delete();
    }
}
