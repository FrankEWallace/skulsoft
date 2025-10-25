<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Services\Contact\PhotoService;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction');
    }

    public function upload(Request $request, string $guardian, PhotoService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $service->upload($request, $guardian->contact);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('contact.props.photo')]),
        ]);
    }

    public function remove(Request $request, string $guardian, PhotoService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $service->remove($request, $guardian->contact);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('contact.props.photo')]),
        ]);
    }
}
