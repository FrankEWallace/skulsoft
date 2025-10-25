<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Services\Form\FormListService;
use Illuminate\Http\Request;

class FormExportController extends Controller
{
    public function __invoke(Request $request, FormListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
