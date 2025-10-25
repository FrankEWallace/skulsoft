<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\PeriodImportRequest;
use App\Models\Academic\Period;
use App\Services\Academic\PeriodActionService;
use Illuminate\Http\Request;

class PeriodActionController extends Controller
{
    public function select(Request $request, Period $period, PeriodActionService $service)
    {
        $this->authorize('validTeam', $period);

        $service->select($request, $period);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.period.current_period')]),
        ]);
    }

    public function default(Request $request, Period $period, PeriodActionService $service)
    {
        $this->authorize('validTeam', $period);

        $service->default($request, $period);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.period.default_period')]),
        ]);
    }

    public function import(PeriodImportRequest $request, Period $period, PeriodActionService $service)
    {
        $this->authorize('create', Period::class);

        $service->import($request, $period);

        return response()->success([
            'message' => trans('global.imported', ['attribute' => trans('academic.period.period')]),
        ]);
    }
}
