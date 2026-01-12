<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Domain\Academic\Models\Batch;
use App\Services\Academic\BatchActionService;
use Illuminate\Http\Request;

class BatchActionController extends Controller
{
    public function updateConfig(Request $request, string $batch, BatchActionService $service)
    {
        $batch = Batch::findByUuidOrFail($batch);

        $this->authorize('update', $batch);

        $service->updateConfig($request, $batch);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.batch.batch')]),
        ]);
    }

    public function updateCurrentPeriod(Request $request, string $batch, BatchActionService $service)
    {
        $batch = Batch::findByUuidOrFail($batch);

        $this->authorize('update', $batch);

        $service->updateCurrentPeriod($request, $batch);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.batch.batch')]),
        ]);
    }
}
