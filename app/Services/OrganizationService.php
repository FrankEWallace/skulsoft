<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    public function create(Request $request): Organization
    {
        \DB::beginTransaction();

        $organization = Organization::forceCreate($this->formatParams($request));

        \DB::commit();

        return $organization;
    }

    private function formatParams(Request $request, ?Organization $organization = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'website' => $request->website,
            'address' => $request->address,
        ];

        return $formatted;
    }

    public function update(Request $request, Organization $organization): void
    {
        \DB::beginTransaction();

        $organization->forceFill($this->formatParams($request, $organization))->save();

        \DB::commit();
    }

    public function deletable(Organization $organization): void
    {
        if (! \Auth::user()->is_default) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }
}
