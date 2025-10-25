<?php

namespace App\Services;

use App\Enums\DeviceType;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceService
{
    public function create(Request $request): Device
    {
        \DB::beginTransaction();

        $device = Device::forceCreate($this->formatParams($request));

        \DB::commit();

        return $device;
    }

    private function formatParams(Request $request, ?Device $device = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'token' => $request->token,
        ];

        if (is_null($device)) {
            $formatted['team_id'] = auth()->user()->current_team_id;
            $formatted['type'] = DeviceType::BIOMETRIC_ATTENDANCE->value;
            $formatted['token'] = Str::random(32);
        }

        return $formatted;
    }

    public function update(Request $request, Device $device): void
    {
        \DB::beginTransaction();

        $device->forceFill($this->formatParams($request, $device))->save();

        \DB::commit();
    }

    public function deletable(Device $device): void {}
}
