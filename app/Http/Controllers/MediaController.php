<?php

namespace App\Http\Controllers;

use App\Http\Requests\MediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['test.mode.restriction']);
    }

    public function store(MediaRequest $request, MediaService $service): MediaResource
    {
        $media = $service->store($request);

        return MediaResource::make($media);
    }

    public function destroy(Request $request, Media $media, MediaService $service)
    {
        $service->delete($request, $media);

        return response()->success([]);
    }
}
