<?php

namespace App\Services\Post;

use App\Concerns\HasStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostImageService
{
    use HasStorage;

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:10240'],
        ]);

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'post/temp',
            input: 'file',
            maxWidth: 1280,
            thumbnail: true,
        );

        return $image;
    }

    public function deleteImage(Request $request)
    {
        $this->deleteImageFile(
            visibility: 'public',
            path: $request->image,
        );

        $thumbFilename = Str::of($request->image)->replaceLast('.', '-thumb.');

        $this->deleteImageFile(
            visibility: 'public',
            path: $thumbFilename,
        );
    }
}
