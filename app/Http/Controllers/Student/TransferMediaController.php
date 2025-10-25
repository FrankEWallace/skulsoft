<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\TransferMediaService;
use Illuminate\Http\Request;

class TransferMediaController extends Controller
{
    public function store(Request $request, string $student, TransferMediaService $service)
    {
        $student = Student::findTransferredByUuidOrFail($student);

        $this->authorize('transfer', Student::class);

        $service->create($request, $student);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('general.file')]),
        ]);
    }
}
