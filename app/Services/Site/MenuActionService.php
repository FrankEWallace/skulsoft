<?php

namespace App\Services\Site;

use App\Models\Site\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MenuActionService
{
    public function reorder(Request $request): void
    {
        $headerMenus = $request->header_menus ?? [];
        $footerMenus = $request->footer_menus ?? [];

        $menus = Menu::query()
            ->whereNull('parent_id')
            ->get();

        foreach ($headerMenus as $index => $menuItem) {
            $menu = $menus->firstWhere('uuid', Arr::get($menuItem, 'uuid'));

            if (! $menu) {
                continue;
            }

            $menu->position = $index + 1;
            $menu->save();
        }

        foreach ($footerMenus as $index => $menuItem) {
            $menu = $menus->firstWhere('uuid', Arr::get($menuItem, 'uuid'));

            if (! $menu) {
                continue;
            }

            $menu->position = $index + 1;
            $menu->save();
        }
    }

    public function reorderSubMenu(Request $request): void
    {
        $menu = Menu::query()
            ->where('uuid', $request->menu)
            ->whereNull('parent_id')
            ->firstOrFail();

        $subMenus = Menu::query()
            ->whereParentId($menu->id)
            ->get();

        foreach ($request->menus as $index => $menuItem) {
            $subMenu = $subMenus->firstWhere('uuid', Arr::get($menuItem, 'uuid'));

            if (! $subMenu) {
                continue;
            }

            $subMenu->position = $index + 1;
            $subMenu->save();
        }
    }
}
