<?php

namespace App\Http\Controllers\Config;

use App\Actions\Config\Locale\CreateLocale;
use App\Actions\Config\Locale\DeleteLocale;
use App\Actions\Config\Locale\UpdateLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Config\LocaleRequest;
use App\Services\Config\LocaleListService;
use App\Services\Config\LocaleService;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function index(Request $request, LocaleListService $service)
    {
        return response()->ok($service->paginate($request));
    }

    public function store(LocaleRequest $request, CreateLocale $action)
    {
        $action->execute($request);

        return response()->success(['message' => trans('global.stored', ['attribute' => trans('config.locale.locale')])]);
    }

    public function show(string $locale, LocaleService $service)
    {
        return response()->ok($service->find($locale));
    }

    public function update(LocaleRequest $request, string $locale, LocaleService $service, UpdateLocale $action)
    {
        $service->find($locale);

        $service->isDefault($locale);

        $action->execute($request, $locale);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('config.locale.locale')])]);
    }

    public function destroy(string $locale, LocaleService $service, DeleteLocale $action)
    {
        $service->find($locale);

        $service->isDefault($locale);

        $service->isSystemDefault($locale);

        $action->execute($locale);

        return response()->success(['message' => trans('global.deleted', ['attribute' => trans('config.locale.locale')])]);
    }
}
