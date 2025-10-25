<?php

namespace App\View\Components\Site;

use App\Enums\Site\MenuPlacement;
use App\Models\Site\Menu;
use Illuminate\View\Component;

class Footer extends Component
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
        $footerMenus = Menu::query()
            ->wherePlacement(MenuPlacement::FOOTER)
            ->whereNull('parent_id')
            ->orderBy('position', 'asc')
            ->get();

        return view()->first(['components.site.custom.footer', 'components.site.default.footer'], compact('footerMenus'));
    }
}
