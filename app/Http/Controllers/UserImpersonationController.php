<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthUserResource;
use App\Services\UserImpersonationService;
use Illuminate\Http\Request;

class UserImpersonationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user:impersonate')->only('impersonate');
    }

    public function impersonate(Request $request, string $uuid, UserImpersonationService $service)
    {
        $user = $service->impersonate($uuid);

        return [
            'user' => AuthUserResource::make($user),
        ];
    }

    public function unimpersonate(Request $request, UserImpersonationService $service)
    {
        $user = $service->unimpersonate();

        return [
            'user' => AuthUserResource::make($user),
        ];
    }
}
