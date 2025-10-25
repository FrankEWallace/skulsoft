<?php

namespace App\Observers;

use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    /**
     * Handle the Activity "saving" event.
     *
     * @param  Spatie\Activitylog\Models\Activity  $activity
     * @return void
     */
    public function saving(Activity $activity)
    {
        $activity->properties = $activity->properties->put('ip', \Request::ip());
        $activity->properties = $activity->properties->put('user_agent', \Request::header('User-Agent'));
    }

    /**
     * Handle the Activity "created" event.
     *
     * @param  Spatie\Activitylog\Models\Activity  $activity
     * @return void
     */
    public function created(Activity $activity)
    {
        //
    }

    /**
     * Handle the Activity "updated" event.
     *
     * @param  Spatie\Activitylog\Models\Activity  $activity
     * @return void
     */
    public function updated(Activity $activity)
    {
        //
    }

    /**
     * Handle the Activity "deleted" event.
     *
     * @param  Spatie\Activitylog\Models\Activity  $activity
     * @return void
     */
    public function deleted(Activity $activity)
    {
        //
    }

    /**
     * Handle the Activity "forceDeleted" event.
     *
     * @param  Spatie\Activitylog\Models\Activity  $activity
     * @return void
     */
    public function forceDeleted(Activity $activity)
    {
        //
    }
}
