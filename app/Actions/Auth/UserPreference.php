<?php

namespace App\Actions\Auth;

use App\Http\Resources\AuthUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserPreference
{
    public function execute(Request $request)
    {
        $user = \Auth::user();

        $preference = $user->user_preference;

        $preference['system'] = [
            'locale' => $request->locale ?? Arr::get($preference, 'system.locale'),
            'date_format' => $request->date_format ?? Arr::get($preference, 'system.date_format'),
            'time_format' => $request->time_format ?? Arr::get($preference, 'system.time_format'),
            'timezone' => $request->timezone ?? Arr::get($preference, 'system.timezone'),
        ];
        $preference['layout'] = [
            'sidebar' => $request->sidebar ?? Arr::get($preference, 'layout.sidebar'),
            'display' => $request->display ?? Arr::get($preference, 'layout.display'),
        ];

        $user->preference = $preference;

        $user->save();

        return AuthUserResource::make($user);
    }
}
