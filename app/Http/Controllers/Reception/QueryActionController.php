<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Reception\Query;
use App\Services\Reception\QueryActionService;
use Illuminate\Http\Request;

class QueryActionController extends Controller
{
    public function action(Request $request, string $query, QueryActionService $service)
    {
        $query = Query::findByUuidOrFail($query);

        $this->authorize('action', $query);

        $service->action($request, $query);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.query.props.status')]),
        ]);
    }
}
