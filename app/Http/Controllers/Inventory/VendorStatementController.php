<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Vendor;
use App\Services\Inventory\VendorStatementService;
use Illuminate\Http\Request;

class VendorStatementController extends Controller
{
    public function __invoke(Request $request, VendorStatementService $service)
    {
        $this->authorize('view', Vendor::class);

        return $service->paginate($request);
    }
}
