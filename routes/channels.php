<?php

use App\Models\Chat\Chat;
use App\Support\SetConfig;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

if (! app()->environment('testing')) {
    (new SetConfig)->set();
}

Broadcast::channel('chats.{chatUuid}', function ($user, $chatUuid) {
    return Chat::query()
        ->where('uuid', $chatUuid)
        ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
        ->exists();
});

Broadcast::channel('users.{uuid}', function ($user, $userUuid) {
    return $user->uuid == $userUuid;
});
