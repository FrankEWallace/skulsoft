<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ValidateIp
{
    public function execute(User $user, string $ip, array $params = []): void
    {
        if ($user->is_default) {
            return;
        }

        $result = $this->validateWhitelistIps($user, $ip, $params);

        if (! $result) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('config.system.not_allowed_ip')]);
        }

        $result = $this->validateBlacklistIps($user, $ip, $params);

        if (! $result) {
            $user->logout();
            throw ValidationException::withMessages(['message' => trans('config.system.not_allowed_ip')]);
        }
    }

    private function validateWhitelistIps(User $user, string $ip, array $params = []): bool
    {
        $data = $this->formatIps(config('config.system.whitelist_ips'));

        if (empty($data)) {
            return true;
        }

        foreach ($data as $item) {
            $roles = Arr::get($item, 'roles', []);
            $ips = Arr::get($item, 'ips', []);

            if (empty($roles) || empty($ips)) {
                continue;
            }

            if (in_array('all', $roles) || array_intersect($roles, $user->user_role)) {
                if (! in_array($ip, $ips)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function validateBlacklistIps(User $user, string $ip, array $params = []): bool
    {
        $data = $this->formatIps(config('config.system.blacklist_ips'));

        if (empty($data)) {
            return true;
        }

        foreach ($data as $item) {
            $roles = Arr::get($item, 'roles', []);
            $ips = Arr::get($item, 'ips', []);

            if (empty($roles) || empty($ips)) {
                continue;
            }

            if (in_array('all', $roles) || array_intersect($roles, $user->user_role)) {
                if (in_array($ip, $ips)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function formatIps(?string $string = null)
    {
        if (empty($string)) {
            return [];
        }

        return collect(explode('|', $string))
            ->map(function ($item) {
                [$roles, $ips] = explode('::', $item);

                return [
                    'roles' => explode(',', $roles),
                    'ips' => explode(',', $ips),
                ];
            })
            ->toArray();
    }
}
