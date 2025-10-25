<?php

namespace App\Services\Site;

use App\Enums\Site\MenuPlacement;
use App\Http\Resources\Site\PageSummaryResource;
use App\Models\Site\Menu;
use App\Models\Site\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuService
{
    public function preRequisite(Request $request): array
    {
        $placements = MenuPlacement::getOptions();

        $pages = PageSummaryResource::collection(Page::query()
            ->get());

        return compact('placements', 'pages');
    }

    public function create(Request $request): Menu
    {
        \DB::beginTransaction();

        $menu = Menu::forceCreate($this->formatParams($request));

        \DB::commit();

        return $menu;
    }

    private function formatParams(Request $request, ?Menu $menu = null): array
    {
        $formatted = [
            'name' => $request->name,
            'placement' => $request->placement,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
            'page_id' => $request->page_id,
        ];

        $meta = $menu?->meta ?? [];
        $meta['has_external_url'] = $request->boolean('has_external_url');
        $meta['external_url'] = $request->has('external_url') ? $request->external_url : null;
        $formatted['meta'] = $meta;

        if (! $menu) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Menu $menu): void
    {
        \DB::beginTransaction();

        $menu->forceFill($this->formatParams($request, $menu))->save();

        \DB::commit();
    }

    public function deletable(Menu $menu): void {}
}
