<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\PromotionRequest;
use App\Models\Student\Student;
use App\Services\Student\PromotionService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware('super.admin')->only('cancel');
    }

    public function preRequisite(Request $request, PromotionService $service)
    {
        $this->authorize('promotion', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, PromotionService $service)
    {
        $this->authorize('promotion', Student::class);

        return $service->fetch($request);
    }

    public function store(PromotionRequest $request, PromotionService $service)
    {
        $this->authorize('promotion', Student::class);

        $service->store($request);

        if ($request->boolean('mark_as_alumni')) {
            return response()->success([
                'message' => trans('student.alumni.marked_as_alumni'),
            ]);
        }

        return response()->success([
            'message' => trans('student.promotion.promoted'),
        ]);
    }

    public function cancel(Request $request, PromotionService $service)
    {
        return $service->cancel($request);
    }
}
