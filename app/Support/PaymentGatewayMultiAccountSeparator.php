<?php

namespace App\Support;

use Illuminate\Support\Str;

trait PaymentGatewayMultiAccountSeparator
{
    public function getFirstAccount(string $string)
    {
        if (! Str::of($string)->contains('::')) {
            return;
        }

        $withAccount = Str::of($string)->explode(',')->first();

        return Str::of($withAccount)->explode('::')->last();
    }

    public function getCredential(string $string, ?string $account = null)
    {
        if (empty($account)) {
            return $string;
        }

        if (! Str::of($string)->contains('::')) {
            return $string;
        }

        $withAccount = Str::of($string)->explode(',')->filter(function ($value, $key) use ($account) {
            return Str::of($value)->explode('::')->last() == $account;
        })->first();

        return Str::of($withAccount)->explode('::')->first();
    }
}
