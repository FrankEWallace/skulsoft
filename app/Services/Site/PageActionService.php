<?php

namespace App\Services\Site;

use App\Concerns\HasStorage;
use App\Models\Site\Block;
use App\Models\Site\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PageActionService
{
    use HasStorage;

    public function updateMeta(Request $request, Page $page)
    {
        $request->validate([
            'seo.robots' => 'boolean',
            'seo.is_public' => 'boolean',
            'seo.slug' => 'nullable|min:3|max:255',
            'seo.meta_title' => 'nullable|min:3|max:255',
            'seo.meta_description' => 'nullable|min:3|max:255',
            'seo.meta_keywords' => 'nullable|min:3|max:255',
        ], [], [
            'seo.robots' => trans('site.seo.robots'),
            'seo.is_public' => trans('site.seo.is_public'),
            'seo.slug' => trans('site.seo.slug'),
            'seo.meta_title' => trans('site.seo.meta_title'),
            'seo.meta_description' => trans('site.seo.meta_description'),
            'seo.meta_keywords' => trans('site.seo.meta_keywords'),
        ]);

        $page->seo = [
            'robots' => $request->boolean('seo.robots'),
            'is_public' => $request->boolean('seo.is_public'),
            'slug' => $request->input('seo.slug'),
            'meta_title' => $request->input('seo.meta_title'),
            'meta_description' => $request->input('seo.meta_description'),
            'meta_keywords' => $request->input('seo.meta_keywords'),
        ];
        $page->save();

        $page->updateMedia($request);
    }

    public function updateBlocks(Request $request, Page $page)
    {
        $request->validate([
            'has_block' => 'boolean',
            'blocks' => 'array|required_if:has_block,true',
        ], [], [
            'has_block' => trans('site.block.block'),
            'blocks' => trans('site.block.block'),
        ]);

        if (! $request->boolean('has_block')) {
            $page->setMeta([
                'has_block' => false,
            ]);
            $page->save();

            return;
        }

        $blocks = Block::query()
            ->whereIn('uuid', $request->blocks)
            ->get();

        $page->setMeta([
            'has_block' => true,
            'blocks' => $blocks->pluck('uuid'),
        ]);
        $page->save();
    }

    public function updateSlider(Request $request, Page $page)
    {
        $request->validate([
            'has_slider' => 'boolean',
            'slider' => 'required_if:has_slider,true',
        ], [], [
            'has_slider' => trans('site.block.props.slider'),
            'slider' => trans('site.block.props.slider'),
        ]);

        if (! $request->boolean('has_slider')) {
            $page->setMeta([
                'has_slider' => false,
            ]);
            $page->save();

            return;
        }

        $slider = Block::query()
            ->where('uuid', $request->slider)
            ->where('meta->is_slider', true)
            ->firstOrFail();

        $page->setMeta([
            'has_slider' => true,
            'slider' => $slider->uuid,
        ]);
        $page->save();
    }

    public function updateCTA(Request $request, Page $page)
    {
        $request->validate([
            'has_cta' => 'boolean',
            'cta_title' => 'required_if:has_cta,true|min:3|max:255',
            'cta_description' => 'required_if:has_cta,true|min:3|max:255',
            'cta_button_text' => 'required_if:has_cta,true|min:3|max:255',
            'cta_button_link' => 'required_if:has_cta,true|url',
        ], [], [
            'has_cta' => trans('site.block.props.cta'),
            'cta_title' => trans('site.block.props.cta_title'),
            'cta_description' => trans('site.block.props.cta_description'),
            'cta_button_text' => trans('site.block.props.cta_button_text'),
            'cta_button_link' => trans('site.block.props.cta_button_link'),
        ]);

        if (! $request->boolean('has_cta')) {
            $page->setMeta([
                'has_cta' => false,
            ]);
            $page->save();

            return;
        }

        $page->setMeta([
            'has_cta' => true,
            'cta_title' => $request->cta_title,
            'cta_description' => $request->cta_description,
            'cta_button_text' => $request->cta_button_text,
            'cta_button_link' => $request->cta_button_link,
        ]);
        $page->save();
    }

    public function uploadAsset(Request $request, Page $page, string $type)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $assets = $page->assets;
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'site/page/assets/'.$type,
            input: 'image',
            url: false
        );

        $assets[$type] = $image;
        $page->assets = $assets;
        $page->save();
    }

    public function removeAsset(Request $request, Page $page, string $type)
    {
        $assets = $page->assets;
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        unset($assets[$type]);
        $page->assets = $assets;
        $page->save();
    }
}
