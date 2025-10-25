<?php

namespace App\Services\Config;

use App\Models\Config\Template;
use Illuminate\Http\Request;

class TemplateActionService
{
    public function updateStatus(Request $request, Template $template)
    {
        $template->enabled_at = $template->enabled_at->value ? null : now()->toDateTimeString();
        $template->save();
    }
}
