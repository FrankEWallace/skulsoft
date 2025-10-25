<?php

namespace App\Actions\Config\Locale;

use App\Concerns\LocalStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CreateLocale
{
    use LocalStorage;

    protected $storage_key = 'locales';

    public function execute(Request $request)
    {
        $collection = $this->validate($request);

        $collection->prepend([
            'uuid' => $request->code,
            'code' => $request->code,
            'name' => $request->name,
        ]);

        if ($request->code != 'en') {
            \File::copyDirectory(base_path('lang/en'), base_path('lang/'.$request->code));
        }

        $this->setStorage($this->storage_key, $collection->all());
    }

    private function validate(Request $request): Collection
    {
        $collection = collect($this->getKey($this->storage_key));

        if (in_array($request->name, Arr::pluck($collection, 'name'))) {
            throw ValidationException::withMessages(['name' => __('validation.unique', ['attribute' => __('config.locale.props.name')])]);
        }

        if (in_array($request->code, Arr::pluck($collection, 'code'))) {
            throw ValidationException::withMessages(['code' => __('validation.unique', ['attribute' => __('config.locale.props.code')])]);
        }

        return $collection;
    }
}
