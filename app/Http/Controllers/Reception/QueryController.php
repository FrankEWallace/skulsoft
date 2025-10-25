<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reception\QueryResource;
use App\Models\Reception\Query;
use App\Services\Reception\QueryListService;
use App\Services\Reception\QueryService;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:query:manage')->only(['store']);
    }

    public function preRequisite(Request $request, QueryService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, QueryListService $service)
    {
        $this->authorize('viewAny', Query::class);

        return $service->paginate($request);
    }

    public function show(string $query, QueryService $service)
    {
        $this->authorize('view', Query::class);

        $query = Query::findByUuidOrFail($query);

        return QueryResource::make($query);
    }

    public function destroy(Request $request, string $query, QueryService $service)
    {
        $query = Query::findByUuidOrFail($query);

        $this->authorize('delete', $query);

        $service->deletable($request, $query);

        $query->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.query.query')]),
        ]);
    }
}
