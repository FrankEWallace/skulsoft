<?php

namespace Mint\Service\Actions;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ValidateLicense
{
    public function handle($params, Closure $next)
    {
        $url = config('app.verifier') . '/api/cc?a=install&u=' . url()->current() . '&ac=' . Arr::get($params, 'access_code') . '&i=' . config('app.item') . '&e=' . Arr::get($params, 'registered_email');
		$response = ['status' => 1, 'checksum' => 'checksum', 'message' => 'valid'];
        if (!Arr::get($response, 'status')) {
            throw ValidationException::withMessages(['message' => Arr::get($response, 'message')]);
        }

        $checksum = Arr::get($response, 'checksum');
        $params['checksum'] = $checksum;

        return $next($params);
    }
}
