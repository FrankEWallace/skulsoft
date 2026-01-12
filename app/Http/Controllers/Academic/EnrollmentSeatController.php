<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\EnrollmentSeatRequest;
use App\Http\Resources\Academic\EnrollmentSeatResource;
use App\Domain\Academic\Models\EnrollmentSeat;
use App\Services\Academic\EnrollmentSeatListService;
use App\Services\Academic\EnrollmentSeatService;
use Illuminate\Http\Request;

class EnrollmentSeatController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:course:read')->only(['preRequisite', 'index']);
        $this->middleware('permission:course:edit')->only(['store', 'update', 'destroy']);
    }

    public function preRequisite(Request $request, EnrollmentSeatService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, EnrollmentSeatListService $service)
    {
        return $service->paginate($request);
    }

    public function store(EnrollmentSeatRequest $request, EnrollmentSeatService $service)
    {
        $enrollmentSeat = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.enrollment_seat.enrollment_seat')]),
            'course' => EnrollmentSeatResource::make($enrollmentSeat),
        ]);
    }

    public function show(Request $request, string $enrollmentSeat, EnrollmentSeatService $service)
    {
        $enrollmentSeat = EnrollmentSeat::findByUuidOrFail($enrollmentSeat);

        $enrollmentSeat->load('enrollmentType', 'course');

        return EnrollmentSeatResource::make($enrollmentSeat);
    }

    public function update(EnrollmentSeatRequest $request, string $enrollmentSeat, EnrollmentSeatService $service)
    {
        $enrollmentSeat = EnrollmentSeat::findByUuidOrFail($enrollmentSeat);

        $service->update($request, $enrollmentSeat);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.enrollment_seat.enrollment_seat')]),
        ]);
    }

    public function destroy(string $enrollmentSeat, EnrollmentSeatService $service)
    {
        $enrollmentSeat = EnrollmentSeat::findByUuidOrFail($enrollmentSeat);

        $service->deletable($enrollmentSeat);

        $enrollmentSeat->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.enrollment_seat.enrollment_seat')]),
        ]);
    }
}
