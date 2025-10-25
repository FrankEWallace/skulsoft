<?php

namespace App\Actions\Config\Module;

class StoreSiteConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_site' => 'boolean',
            'show_public_view' => 'boolean',
            'color_scheme' => 'nullable|string',
            'google_map_embed_url' => 'nullable|url',
        ], [], [
            'enable_site' => __('site.site'),
            'show_public_view' => __('site.config.props.public_view'),
            'color_scheme' => __('site.config.props.color_scheme'),
            'google_map_embed_url' => __('site.config.props.google_map_embed_url'),
        ]);

        return $input;
    }
}
