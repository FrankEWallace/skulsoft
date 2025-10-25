<?php

namespace App\Http\Controllers;

use App\Http\Requests\GalleryRequest;
use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use App\Services\GalleryListService;
use App\Services\GalleryService;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function preRequisite(Request $request, GalleryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, GalleryListService $service)
    {
        $this->authorize('viewAny', Gallery::class);

        return $service->paginate($request);
    }

    public function store(GalleryRequest $request, GalleryService $service)
    {
        $this->authorize('create', Gallery::class);

        $gallery = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('gallery.gallery')]),
            'gallery' => GalleryResource::make($gallery),
        ]);
    }

    public function show(string $gallery, GalleryService $service): GalleryResource
    {
        $gallery = Gallery::findByUuidOrFail($gallery);

        $this->authorize('view', $gallery);

        $gallery->load('images');

        return GalleryResource::make($gallery);
    }

    public function update(GalleryRequest $request, string $gallery, GalleryService $service)
    {
        $gallery = Gallery::findByUuidOrFail($gallery);

        $this->authorize('update', $gallery);

        $service->update($request, $gallery);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('gallery.gallery')]),
        ]);
    }

    public function destroy(string $gallery, GalleryService $service)
    {
        $gallery = Gallery::findByUuidOrFail($gallery);

        $this->authorize('delete', $gallery);

        $service->deletable($gallery);

        $service->delete($gallery);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('gallery.gallery')]),
        ]);
    }
}
