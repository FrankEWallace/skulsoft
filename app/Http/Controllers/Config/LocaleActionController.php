<?php

namespace App\Http\Controllers\Config;

use App\Actions\Config\Locale\SyncLocale;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocaleActionController extends Controller
{
    public function sync(Request $request, string $locale, SyncLocale $action)
    {
        $action->execute($locale);

        return response()->success(['message' => trans('global.synched', ['attribute' => trans('config.locale.locale')])]);
    }
}
