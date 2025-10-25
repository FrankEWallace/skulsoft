<?php

namespace App\Http\Controllers;

use App\Services\UserListService;
use Illuminate\Http\Request;

class UserExportController extends Controller
{
    public function __invoke(Request $request, UserListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
