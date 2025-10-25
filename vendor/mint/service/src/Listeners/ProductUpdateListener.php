<?php

namespace Mint\Service\Listeners;

use App\Helpers\SysHelper;
use Illuminate\Support\Arr;
use Mint\Service\Events\ProductUpdate;

class ProductUpdateListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Auth\UserLogin  $event
     * @return void
     */
    public function handle(ProductUpdate $event)
    {
        if (!Arr::get($event->data, 'status')) {
            SysHelper::resetApp();
        }
    }
}
