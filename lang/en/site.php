<?php

return [
    'site' => 'Site',
    'config' => [
        'props' => [
            'public_view' => 'Show in public',
            'color_scheme' => 'Color Scheme',
            'google_map_embed_url' => 'Google Map Embed URL',
        ],
    ],
    'page' => [
        'page' => 'Page',
        'pages' => 'Pages',
        'module_title' => 'List all pages',
        'module_description' => 'List all pages',
        'props' => [
            'name' => 'Name',
            'title' => 'Title',
            'slug' => 'Slug',
            'sub_title' => 'Sub Title',
            'content' => 'Content',
        ],
    ],
    'seo' => [
        'seo' => 'SEO',
        'is_public' => 'Is Public',
        'slug' => 'Slug',
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_keywords' => 'Meta Keywords',
        'robots' => 'Allow discovery by search engines',
    ],
    'assets' => [
        'cover' => 'Cover',
        'og' => 'OG',
        'custom_og' => 'Custom OG Image',
        'og_info' => 'Upload an OG Image (600x315) for this blog. This image will be used when you share this item on social media.',
    ],
    'menu' => [
        'menu' => 'Menu',
        'menus' => 'Menus',
        'module_title' => 'List all menus',
        'module_description' => 'List all menus',
        'could_not_have_nested_menu' => 'Could not have nested menu.',
        'props' => [
            'name' => 'Name',
            'placement' => 'Placement',
            'parent' => 'Parent',
            'external_url' => 'External URL',
        ],
        'placements' => [
            'header' => 'Header',
            'footer' => 'Footer',
            'other' => 'Other',
        ],
    ],
    'block' => [
        'block' => 'Block',
        'blocks' => 'Blocks',
        'module_title' => 'List all blocks',
        'module_description' => 'List all blocks',
        'props' => [
            'name' => 'Name',
            'title' => 'Title',
            'sub_title' => 'Sub Title',
            'content' => 'Content',
            'url' => 'URL',
            'slider' => 'Slider',
            'slider_image' => 'Slider Image',
            'cta' => 'Call To Action',
            'cta_title' => 'Title',
            'cta_description' => 'Description',
            'cta_button_text' => 'Button Text',
            'cta_button_link' => 'Button Link',
        ],
    ],
];
