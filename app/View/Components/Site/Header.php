<?php

namespace App\View\Components\Site;

use App\Enums\Site\MenuPlacement;
use App\Models\Site\Menu;
use Illuminate\View\Component;

class Header extends Component
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
        $headerMenus = Menu::query()
            ->wherePlacement(MenuPlacement::HEADER)
            ->with(['children' => function ($query) {
                $query->whereNotNull('page_id')
                    ->orderBy('position', 'asc');
            }])
            ->whereNull('parent_id')
            ->orderBy('position', 'asc')
            ->get();

        return view()->first(['components.site.custom.header', 'components.site.default.header'], compact('headerMenus'));
    }
}
