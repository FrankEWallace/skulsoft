<?php

namespace App\Actions\Config;

use App\Models\User;
use App\Notifications\TestNotificationWithoutQueue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TestMailConnection
{
    public function execute(Request $request)
    {
        $user = User::first();

        try {
            $user->notify(new TestNotificationWithoutQueue($user->name));
        } catch (Exception $e) {
            throw ValidationException::withMessages(['message' => $e->getMessage()]);
        }
    }
}
