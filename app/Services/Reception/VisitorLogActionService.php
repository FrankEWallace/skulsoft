<?php

namespace App\Services\Reception;

use App\Models\Reception\VisitorLog;
use Illuminate\Http\Request;

class VisitorLogActionService
{
    public function markExit(Request $request, VisitorLog $visitorLog): void
    {
        $visitorLog->exit_at = now()->toDateTimeString();
        $visitorLog->save();
    }
}
