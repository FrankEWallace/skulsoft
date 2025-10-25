<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ScreenLock;
use App\Actions\Auth\ScreenUnlock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ScreenUnlockRequest;
use Illuminate\Http\Request;

class ScreenLockController extends Controller
{
    /**
     * Lock screen
     */
    public function lock(Request $request, ScreenLock $lock)
    {
        $lock->execute($request);

        return response()->ok([]);
    }

    /**
     * Unlock screen
     */
    public function unlock(ScreenUnlockRequest $request, ScreenUnlock $unlock)
    {
        $unlock->execute($request);

        return response()->ok([]);
    }
}
