<?php

namespace App\Services\Config;

use App\Concerns\CollectionPaginator;
use App\Concerns\HasPagination;
use App\Concerns\LocalStorage;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class LocaleService
{
    use CollectionPaginator, HasPagination, LocalStorage;

    protected $storage_key = 'locales';

    /**
     * Find locale
     */
    public function find(string $code): array
    {
        $collection = collect($this->getLocales());

        $filtered = $collection->filter(function ($item, $key) use ($code) {
            return Arr::get($item, 'code') === $code;
        });

        if (! $filtered->count()) {
            throw ValidationException::withMessages(['message' => __('global.could_not_find', ['attribute' => __('config.locale.locale')])]);
        }

        return Arr::first($filtered->all());
    }

    /**
     * Get locales
     */
    public function getLocales(): array
    {
        return $this->getKey($this->storage_key) ?? [];
    }

    /**
     * Get all locale modules
     */
    public function getModules(): array
    {
        $modules = [];
        foreach (\File::allFiles(base_path('lang/en')) as $file) {
            $modules[] = basename($file, '.php');
        }

        return $modules;
    }

    /**
     * Paginate all locales
     */
    public function paginate()
    {
        return $this->collectionPaginate($this->getLocales(), $this->getPageLength(), $this->getCurrentPage());
    }

    /**
     * Check for default locale
     */
    public function isDefault(string $locale): void
    {
        if ($locale === 'en') {
            throw ValidationException::withMessages(['message' => __('global.could_not_modify_default', ['attribute' => __('config.locale.locale')])]);
        }
    }

    public function isSystemDefault(string $locale): void
    {
        if ($locale === config('app.locale')) {
            throw ValidationException::withMessages(['message' => __('global.could_not_modify_default', ['attribute' => __('config.locale.locale')])]);
        }
    }
}
