<?php

namespace App\Actions\Config\Module;

class StoreBlogConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_blog' => 'boolean',
        ], [], [
            'enable_blog' => __('global.enable', ['attribute' => __('blog.blog')]),
        ]);

        return $input;
    }
}
