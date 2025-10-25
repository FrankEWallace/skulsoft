<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomFieldRequest;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use App\Services\CustomFieldListService;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:custom-field:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CustomFieldService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CustomFieldListService $service)
    {
        return $service->paginate($request);
    }

    public function store(CustomFieldRequest $request, CustomFieldService $service)
    {
        $customField = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('custom_field.custom_field')]),
            'custom_field' => CustomFieldResource::make($customField),
        ]);
    }

    public function show(string $customField, CustomFieldService $service)
    {
        $customField = CustomField::findByUuidOrFail($customField);

        return CustomFieldResource::make($customField);
    }

    public function update(CustomFieldRequest $request, string $customField, CustomFieldService $service)
    {
        $customField = CustomField::findByUuidOrFail($customField);

        $service->update($request, $customField);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('custom_field.custom_field')]),
        ]);
    }

    public function destroy(string $customField, CustomFieldService $service)
    {
        $customField = CustomField::findByUuidOrFail($customField);

        $service->deletable($customField);

        $customField->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('custom_field.custom_field')]),
        ]);
    }
}
