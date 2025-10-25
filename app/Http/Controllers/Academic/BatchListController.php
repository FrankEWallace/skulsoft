<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Services\Academic\BatchSubjectListService;
use Illuminate\Http\Request;

class BatchListController extends Controller
{
    public function __construct()
    {
        //
    }

    public function subjects(Request $request, string $batch, BatchSubjectListService $service)
    {
        $batch = Batch::findByUuidOrFail($batch);

        $this->authorize('viewAny', Batch::class);

        return $service->paginate($request, $batch);
    }
}
