<?php

namespace App\Services\Reception;

use App\Models\Reception\Query;
use Illuminate\Http\Request;

class QueryActionService
{
    public function action(Request $request, Query $query)
    {
        $query->status = $request->status;
        $query->remarks = $request->remarks;
        $query->user_id = auth()->id();
        $query->save();
    }
}
