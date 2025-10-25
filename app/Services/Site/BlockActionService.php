<?php

namespace App\Services\Site;

use App\Concerns\HasStorage;
use App\Models\Site\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BlockActionService
{
    use HasStorage;

    public function reorder(Request $request): void
    {
        $blocks = $request->blocks ?? [];

        $allBlocks = Block::query()
            ->get();

        foreach ($blocks as $index => $blockItem) {
            $block = $allBlocks->firstWhere('uuid', Arr::get($blockItem, 'uuid'));

            if (! $block) {
                continue;
            }

            $block->position = $index + 1;
            $block->save();
        }
    }

    public function uploadAsset(Request $request, Block $block, string $type)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $assets = $block->assets;
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'site/block/assets/'.$type,
            input: 'image',
            url: false
        );

        $assets[$type] = $image;
        $block->assets = $assets;
        $block->save();
    }

    public function removeAsset(Request $request, Block $block, string $type)
    {
        $assets = $block->assets;
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        unset($assets[$type]);
        $block->assets = $assets;
        $block->save();
    }

    public function uploadSliderImage(Request $request, Block $block)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:10240'],
        ]);

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'site/block/slider-image',
            input: 'file',
            maxWidth: 1980,
        );

        $assets = $block->assets ?? [];

        $sliderImage = [
            'uuid' => (string) Str::uuid(),
            'path' => $image,
        ];

        $assets['slider_images'][] = $sliderImage;

        $block->assets = $assets;
        $block->save();

        $sliderImage['url'] = $this->getImageFile(visibility: 'public', path: $image, default: '/images/site/cover.webp');

        return $sliderImage;
    }

    public function deleteSliderImage(Request $request, Block $block, string $image)
    {
        $assets = $block->assets ?? [];
        $sliderImages = collect(Arr::get($assets, 'slider_images', []));

        $sliderImage = $sliderImages->firstWhere('uuid', $image);

        $this->deleteImageFile(
            visibility: 'public',
            path: Arr::get($sliderImage, 'path'),
        );

        $sliderImages = $sliderImages->filter(function ($item) use ($sliderImage) {
            return $item['uuid'] !== $sliderImage['uuid'];
        })->toArray();

        $assets['slider_images'] = $sliderImages;
        $block->assets = $assets;
        $block->save();
    }
}
