<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Services\Utility\BackupListService;
use App\Services\Utility\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.available:feature.enable_backup');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function index(Request $request, BackupListService $service)
    {
        return $service->paginate($request);
    }

    public function generate(BackupService $service)
    {
        \Artisan::call('backup:run');

        return response()->success([
            'message' => trans('global.generated', ['attribute' => trans('utility.backup.backup')]),
        ]);
    }

    public function destroy($uuid, BackupService $service)
    {
        $service->deletable($uuid);

        $service->delete($uuid);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('utility.backup.backup')]),
        ]);
    }
}
