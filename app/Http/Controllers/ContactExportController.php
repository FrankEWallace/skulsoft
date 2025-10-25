<?php

namespace App\Http\Controllers;

use App\Services\ContactListService;
use Illuminate\Http\Request;

class ContactExportController extends Controller
{
    public function __invoke(Request $request, ContactListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
