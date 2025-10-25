<?php

namespace App\Concerns;

use Illuminate\Support\Facades\App;
use Spatie\Valuestore\Valuestore as Storage;

trait LocalStorage
{
    /**
     * Get local storage
     */
    public function getStorage(): Storage
    {
        if (App::environment('testing')) {
            return Storage::make(database_path('storage-test.json'));
        }

        return Storage::make(database_path('storage.json'));
    }

    /**
     * Set local storage
     */
    public function setStorage(string $key, array $items = []): void
    {
        $storage = $this->getStorage();

        $storage->put($key, array_values($items));
    }

    /**
     * Get storage key
     */
    public function getKey($key): mixed
    {
        $storage = $this->getStorage();

        return $storage->get($key) ?? [];
    }
}
