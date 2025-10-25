<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CustomFeeRequest;
use App\Http\Resources\Student\FeeRecordResource;
use App\Models\Student\Student;
use App\Services\Student\CustomFeeListService;
use App\Services\Student\CustomFeeService;
use Illuminate\Http\Request;

class CustomFeeController extends Controller
{
    public function preRequisite(Request $request, string $student, CustomFeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->preRequisite($request, $student));
    }

    public function index(Request $request, string $student, CustomFeeListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(CustomFeeRequest $request, string $student, CustomFeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $customFee = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.fee.custom_fee')]),
            'custom_fee' => FeeRecordResource::make($customFee),
        ]);
    }

    public function show(string $student, string $customFee, CustomFeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $customFee = $service->findByUuidOrFail($student, $customFee);

        $customFee->load('head');

        return FeeRecordResource::make($customFee);
    }

    public function update(CustomFeeRequest $request, string $student, string $customFee, CustomFeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $customFee = $service->findByUuidOrFail($student, $customFee);

        $service->update($request, $student, $customFee);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.fee.custom_fee')]),
        ]);
    }

    public function destroy(string $student, string $customFee, CustomFeeService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('updateFee', $student);

        $customFee = $service->findByUuidOrFail($student, $customFee);

        $service->deletable($student, $customFee);

        $service->delete($student, $customFee);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.fee.custom_fee')]),
        ]);
    }
}
