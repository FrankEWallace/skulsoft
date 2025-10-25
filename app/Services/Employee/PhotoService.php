<?php

namespace App\Services\Employee;

use App\Concerns\HasStorage;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;

class PhotoService
{
    use HasStorage;

    public function upload(Request $request, Employee $employee)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $contact = $employee->contact;

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

    public function remove(Request $request, Employee $employee)
    {
        $contact = $employee->contact;

        $photo = $contact->photo;

        $this->deleteImageFile(
            visibility: 'public',
            path: $photo,
        );

        $contact->photo = null;
        $contact->save();
    }
}
