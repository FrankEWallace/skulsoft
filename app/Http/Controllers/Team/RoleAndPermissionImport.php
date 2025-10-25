<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Services\Team\RoleAndPermissionImportService;
use Illuminate\Http\Request;

class RoleAndPermissionImport extends Controller
{
    public function __invoke(Request $request, RoleAndPermissionImportService $service)
    {
        $service->import($request);

        return response()->success([
            'imported' => true,
            'message' => trans('team.config.role.imported'),
        ]);
    }
}
