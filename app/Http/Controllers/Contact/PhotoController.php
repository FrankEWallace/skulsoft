<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\Contact\PhotoService;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction');
    }

    public function upload(Request $request, string $contact, PhotoService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('update', $contact);

        $service->upload($request, $contact);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function remove(Request $request, string $contact, PhotoService $service)
    {
        $contact = Contact::findByUuidOrFail($contact);

        $this->authorize('update', $contact);

        $service->remove($request, $contact);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('contact.props.photo')]),
        ]);
    }
}
