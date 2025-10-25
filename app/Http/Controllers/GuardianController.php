<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUpdateRequest;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Services\GuardianListService;
use App\Services\GuardianService;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function preRequisite(GuardianService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, GuardianListService $service)
    {
        $this->authorize('viewAny', Guardian::class);

        return $service->paginate($request);
    }

    public function show(string $guardian, GuardianService $service): GuardianResource
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('view', $guardian);

        $guardian->load('contact.user.roles', 'contact.religion', 'contact.caste', 'contact.category');

        return GuardianResource::make($guardian);
    }

    public function update(ContactUpdateRequest $request, string $guardian, GuardianService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $service->update($request, $guardian->contact);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('guardian.guardian')]),
        ]);
    }

    public function destroy(string $guardian, GuardianService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('delete', $guardian);

        $service->deletable($guardian);

        $guardian->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('guardian.guardian')]),
        ]);
    }
}
