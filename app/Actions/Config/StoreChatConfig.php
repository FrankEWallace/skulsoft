<?php

namespace App\Actions\Config;

class StoreChatConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_chat' => 'sometimes|boolean',
        ], [
        ], [
            'enable_chat' => __('config.chat.props.enable_chat'),
        ]);

        return $input;
    }
}
