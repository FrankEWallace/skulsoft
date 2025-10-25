<?php

namespace App\Actions\Config\Module;

class StoreGalleryConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'show_gallery_in_dashboard' => 'sometimes|boolean',
            'enable_watermark' => 'required|boolean',
            'watermark_position' => 'required|string|in:top-left,top-right,bottom-left,bottom-right',
            'watermark_size' => 'required|integer|min:10|max:200',
        ], [], [
            'show_gallery_in_dashboard' => __('gallery.config.props.show_gallery_in_dashboard'),
            'watermark_position' => __('gallery.config.props.watermark_position'),
            'watermark_size' => __('gallery.config.props.watermark_size'),
            'enable_watermark' => __('gallery.config.props.enable_watermark'),
        ]);

        return $input;
    }
}
