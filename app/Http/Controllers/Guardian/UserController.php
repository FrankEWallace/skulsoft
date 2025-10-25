<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\UserRequest;
use App\Http\Requests\Contact\UserUpdateRequest;
use App\Models\Guardian;
use App\Services\Contact\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        // $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function confirm(Request $request, string $guardian, UserService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('view', $guardian);

        return $service->confirm($request, $guardian->contact);
    }

    public function index(string $guardian, UserService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('view', $guardian);

        return response()->ok($service->fetch($guardian->contact));
    }

    public function create(UserRequest $request, string $guardian, UserService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $service->create($request, $guardian->contact);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('guardian.login.login')]),
        ]);
    }

    public function update(UserUpdateRequest $request, string $guardian, UserService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $guardian->load('contact.user');

        $this->denyAdmin($guardian?->contact?->user);

        $service->update($request, $guardian->contact);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('guardian.login.login')]),
        ]);
    }

    public function updateCurrentPeriod(Request $request, string $guardian, UserService $service)
    {
        $guardian = Guardian::findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $guardian->load('contact.user');

        $this->denyAdmin($guardian?->contact?->user);

        $service->updateCurrentPeriod($request, $guardian->contact?->user);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.period.current_period')]),
        ]);
    }
}
