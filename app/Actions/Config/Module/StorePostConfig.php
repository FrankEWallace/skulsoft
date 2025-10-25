<?php

namespace App\Actions\Config\Module;

class StorePostConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_redirect_to_post_after_login' => 'sometimes|boolean',
        ], [], [
            'enable_redirect_to_post_after_login' => __('post.config.props.redirect_to_post_after_login'),
        ]);

        return $input;
    }
}
