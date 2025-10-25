<?php

namespace App\Actions\Config;

class StoreSocialNetworkConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'google' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'youtube' => 'nullable|url',
            'github' => 'nullable|url',
        ], [], [
            'facebook' => __('config.social.props.facebook'),
            'twitter' => __('config.social.props.twitter'),
            'google' => __('config.social.props.google'),
            'linkedin' => __('config.social.props.facebook'),
            'youtube' => __('config.social.props.youtube'),
            'github' => __('config.social.props.github'),
        ]);

        return $input;
    }
}
