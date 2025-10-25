<?php

namespace App\Http\Controllers;

use App\Http\Requests\OptionRequest;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Services\OptionListService;
use App\Services\OptionService;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function preRequisite(OptionService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, OptionListService $service)
    {
        return $service->paginate($request);
    }

    public function store(OptionRequest $request, OptionService $service)
    {
        $option = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => $request->trans]),
            'option' => OptionResource::make($option),
        ]);
    }

    public function show(Option $option, OptionService $service)
    {
        return OptionResource::make($option);
    }

    public function update(OptionRequest $request, Option $option, OptionService $service)
    {
        $service->update($request, $option);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => $request->trans]),
        ]);
    }

    public function destroy(Request $request, Option $option, OptionService $service)
    {
        $service->deletable($option);

        $option->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => $request->trans]),
        ]);
    }
}
