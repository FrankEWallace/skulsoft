<?php

namespace Mint\Service;

use Illuminate\Support\ServiceProvider;

class MintServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/../routes/web.php';
        include __DIR__ . '/../routes/api.php';
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        config([
            'app.item' => '858790',
            'app.verifier' => 'https://' . 'au' . 'th' . '.scr' . 'ipt' . 'mi' . 'nt.' . 'co' . 'm'
        ]);
    }
}
