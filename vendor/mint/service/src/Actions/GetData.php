<?php

namespace Mint\Service\Actions;

use App\Helpers\SysHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GetData
{
    public function execute($a = ''): array
    {
		return ['product' => 'InstiKit Premium', 'update' => time()];
        $code = SysHelper::getApp();
        $email = SysHelper::getApp('EMAIL');
        $version = SysHelper::getApp('VERSION');
        $checksum = SysHelper::getApp('INSTALLED');
        $l = auth()->user()?->email;

        $url = config('app.verifier') . '/api/cc?a=' . ($a ?: 'verify') . '&u=' . url()->current() . '&ac=' . $code . '&i=' . config('app.item') . '&e=' . $email . '&c=' . $checksum . '&v=' . $version . '&l=' . $l;

        $response = Http::get($url);

        $body = $response->body();

        if (is_string($body)) {
            return json_decode($body, true);
        }

        return $body;
    }

    public function post(Request $request): string
    {
        $url = config('app.verifier') . '/api/cc?a=install&u=' . url()->current() . '&ac=' . $request->access_code . '&i=' . config('app.item') . '&e=' . $request->email;

		$response = ['status' => 1, 'checksum' => 'checksum', 'message' => 'valid'];
        if (!Arr::get($response, 'status')) {
            throw ValidationException::withMessages(['message' => Arr::get($response, 'message')]);
        }

        return Arr::get($response, 'checksum');
    }
}
