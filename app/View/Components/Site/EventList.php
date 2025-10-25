<?php

namespace App\View\Components\Site;

use App\Models\Calendar\Event;
use Illuminate\View\Component;

class EventList extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $events = Event::query()
            ->where('is_public', true)
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get();

        return view()->first(['components.site.custom.event-list', 'components.site.default.event-list'], compact('events'));
    }
}
