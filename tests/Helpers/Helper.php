<?php

namespace Tests\Helpers;

use App\Helpers\SysHelper;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class Helper
{
    const PAGINATION_KEYS = ['data', 'meta', 'links', 'headers'];

    public static function getResponseContent($response, $body = null)
    {
        $content = json_decode($response->getContent(), true);

        return $body ? Arr::get($content, $body) : $content;
    }

    public static function generateUser($data = []): User
    {
        return User::factory()->create($data);
    }

    public static function createSuperAdmin($data = [], $teamId = 1): User
    {
        SysHelper::setTeam($teamId);

        $user = User::factory()->create(array_merge([
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'meta' => ['is_default' => true],
        ], $data))->assignRole('admin');

        return $user;
    }

    public static function createAdmin($data = [], $teamId = 1): User
    {
        SysHelper::setTeam($teamId);

        $user = User::factory()->create(array_merge([
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'password' => bcrypt('password'),
        ], $data))->assignRole('admin');

        return $user;
    }

    public static function createUser($data = [], $teamId = 1): User
    {
        SysHelper::setTeam($teamId);

        $user = User::factory()->create(array_merge([
            'email' => 'user@user.com',
            'username' => 'user',
            'password' => bcrypt('password'),
        ], $data))->assignRole('user');

        return $user;
    }

    public static function createAnonymousUser($data = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => 'anonymous@anonymous.com',
            'username' => 'anonymous',
            'password' => bcrypt('password'),
        ], $data));

        return $user;
    }

    public static function getUser($username = 'user'): User
    {
        $user = User::whereUsername($username)->first();

        if (! $user) {
            ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('user.user')])]);
        }

        return $user;
    }

    public static function actingAs(string $email = 'admin@admin.com'): void
    {
        \Auth::once(['email' => $email, 'password' => 'password']);
    }
}
