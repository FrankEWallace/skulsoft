<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $directory = resource_path('views/pages');

        $theme = config('config.site.theme', 'default');

        if ($theme != 'default') {
            $directory = resource_path('views/pages/'.$theme);
        }

        Folio::path($directory)->middleware([
            '*' => [
                'site.enabled',
            ],
        ]);
    }
}
