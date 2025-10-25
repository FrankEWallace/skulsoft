<?php

namespace App\Services\Site;

use App\Http\Resources\Site\MenuResource;
use App\Models\Site\Block;
use App\Models\Site\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlockService
{
    public function preRequisite(Request $request): array
    {
        $menus = MenuResource::collection(Menu::query()->get());

        return compact('menus');
    }

    public function create(Request $request): Block
    {
        \DB::beginTransaction();

        $block = Block::forceCreate($this->formatParams($request));

        \DB::commit();

        return $block;
    }

    private function formatParams(Request $request, ?Block $block = null): array
    {
        $formatted = [
            'name' => Str::upper($request->name),
            'title' => $request->title,
            'sub_title' => $request->sub_title,
            'content' => $request->content,
            'menu_id' => $request->menu_id,
        ];

        $meta = $block?->meta ?? [];
        $meta['url'] = $request->url;
        $meta['is_slider'] = $request->boolean('is_slider');

        $formatted['meta'] = $meta;

        if (! $block) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Block $block): void
    {
        \DB::beginTransaction();

        $block->forceFill($this->formatParams($request, $block))->save();

        \DB::commit();
    }

    public function deletable(Block $block): void {}
}
