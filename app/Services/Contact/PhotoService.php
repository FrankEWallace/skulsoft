<?php

namespace App\Services\Contact;

use App\Concerns\HasStorage;
use App\Models\Contact;
use Illuminate\Http\Request;

class PhotoService
{
    use HasStorage;

    public function upload(Request $request, Contact $contact)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $photo = $contact->photo;

        $this->deleteImageFile(
            visibility: 'public',
            path: $photo,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'photo',
            input: 'image',
            maxWidth: 400,
        );

        $contact->photo = $image;
        $contact->save();
    }

    public function remove(Request $request, Contact $contact)
    {
        $photo = $contact->photo;

        $this->deleteImageFile(
            visibility: 'public',
            path: $photo,
        );

        $contact->photo = null;
        $contact->save();
    }
}
