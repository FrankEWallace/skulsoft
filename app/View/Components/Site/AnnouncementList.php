<?php

namespace App\View\Components\Site;

use Illuminate\View\Component;

class AnnouncementList extends Component
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
        return view()->first(['components.site.custom.announcement-list', 'components.site.default.announcement-list']);
    }
}
