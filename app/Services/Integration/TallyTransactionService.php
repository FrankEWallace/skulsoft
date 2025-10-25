<?php

namespace App\Services\Integration;

use App\Helpers\CalHelper;
use App\Models\Config\Config;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TallyTransactionService
{
    public function getTransactions(Request $request)
    {
        $token = $request->query('token');

        $device = Device::query()
            ->where('token', $token)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Invalid token',
                'code' => 100,
            ], 422);
        }

        $date = $request->query('date');

        if (! $date || ! CalHelper::validateDate($date)) {
            $date = today()->toDateString();
        }

        $startDateTime = CalHelper::storeDateTime($date.' 00:00:00')->toDateTimeString();
        $endDateTime = CalHelper::storeDateTime($date.' 23:59:59')->toDateTimeString();

        $config = Config::query()
            ->where('name', 'system')
            ->first();

        $timezone = Arr::get($config->value, 'timezone', 'UTC');

        // here goes logic to get transactions for tally

        return response()->json([
            'data' => [],
            'code' => 200,
        ], 200);
    }
}
