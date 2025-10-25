<?php

namespace Mint\Service\Actions;

use App\Enums\UserStatus;
use Closure;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeedUser
{
    public function handle($params, Closure $next)
    {
        $user = User::forceCreate([
            'uuid'              => Str::uuid(),
            'name'              => Arr::get($params, 'name'),
            'username'          => Arr::get($params, 'username'),
            'email'             => Arr::get($params, 'email'),
            'password'          => bcrypt(Arr::get($params, 'password')),
            'email_verified_at' => now()->toDateTimeString(),
            'status'            => UserStatus::ACTIVATED
        ]);

        $user->meta = array('is_default' => true);
        $user->save();

        $user->assignRole('admin');

        return $next($params);
    }
}
