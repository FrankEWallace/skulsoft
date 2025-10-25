<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Models\Resource\Diary;
use App\Services\Resource\DiaryPreviewService;
use Illuminate\Http\Request;

class DiaryPreviewController extends Controller
{
    public function __invoke(Request $request, DiaryPreviewService $service)
    {
        $this->authorize('viewAny', Diary::class);

        return $service->preview($request);
    }
}
