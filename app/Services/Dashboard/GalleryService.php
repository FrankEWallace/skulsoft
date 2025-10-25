<?php

namespace App\Services\Dashboard;

use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryService
{
    public function fetch(Request $request)
    {
        return GalleryResource::collection(Gallery::query()
            ->byTeam()
            ->with('images')
            ->withThumbnail()
            ->orderBy('date', 'desc')
            ->limit(3)
            ->get());
    }
}
