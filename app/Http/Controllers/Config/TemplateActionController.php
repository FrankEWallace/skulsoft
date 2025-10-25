<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Config\Template;
use App\Services\Config\TemplateActionService;
use Illuminate\Http\Request;

class TemplateActionController extends Controller
{
    public function updateStatus(Request $request, Template $template, TemplateActionService $service)
    {
        $service->updateStatus($request, $template);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('config.template.template')])]);
    }
}
