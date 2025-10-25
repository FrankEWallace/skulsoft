<?php

namespace App\Http\Controllers;

use App\Http\Resources\GalleryImageResource;
use App\Models\Gallery;
use App\Services\GalleryActionService;
use Illuminate\Http\Request;

class GalleryActionController extends Controller
{
    public function upload(Request $request, Gallery $gallery, GalleryActionService $service)
    {
        $this->authorize('update', $gallery);

        $galleryImage = $service->upload($request, $gallery);

        return GalleryImageResource::make($galleryImage);
    }

    public function makeCover(Request $request, Gallery $gallery, string $image, GalleryActionService $service)
    {
        $this->authorize('update', $gallery);

        $service->makeCover($request, $gallery, $image);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('gallery.props.image')]),
        ]);
    }

    public function deleteImage(Request $request, Gallery $gallery, string $image, GalleryActionService $service)
    {
        $this->authorize('update', $gallery);

        $service->deleteImage($request, $gallery, $image);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('gallery.props.image')]),
        ]);
    }
}
