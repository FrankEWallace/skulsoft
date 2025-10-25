<?php

namespace App\Observers\Academic;

use App\Models\Academic\Period;

class PeriodObserver
{
    /**
     * Handle the Period "created" event.
     *
     * @return void
     */
    public function created(Period $period)
    {
        //
    }

    /**
     * Handle the Period "updated" event.
     *
     * @return void
     */
    public function updated(Period $period)
    {
        //
    }

    /**
     * Handle the Period "deleted" event.
     *
     * @return void
     */
    public function deleted(Period $period) {}

    /**
     * Handle the Period "restored" event.
     *
     * @return void
     */
    public function restored(Period $period)
    {
        //
    }

    /**
     * Handle the Period "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Period $period)
    {
        //
    }
}
