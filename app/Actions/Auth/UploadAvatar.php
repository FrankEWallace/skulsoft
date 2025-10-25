<?php

namespace App\Actions\Auth;

use App\Concerns\HasStorage;
use Illuminate\Http\Request;

class UploadAvatar
{
    use HasStorage;

    public function execute(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        $user = \Auth::user();

        $avatar = $user->getMeta('avatar');

        $this->deleteImageFile(
            visibility: 'public',
            path: $avatar,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'avatar',
            input: 'image',
        );

        $user->updateMeta(['avatar' => $image]);
    }
}
