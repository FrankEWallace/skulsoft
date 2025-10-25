<?php

namespace Mint\Service\Actions;

use App\Actions\CreateContact;
use App\Models\Employee\Employee;
use Closure;
use Illuminate\Support\Arr;

class Setup
{
    public function handle($params, Closure $next)
    {
        return $next($params);
    }
}
