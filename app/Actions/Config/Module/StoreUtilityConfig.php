<?php

namespace App\Actions\Config\Module;

class StoreUtilityConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'todo_view' => 'sometimes|required|in:list,board',
        ], [
            'todo_view' => __('utility.config.props.todo_view'),
        ], []);

        return $input;
    }
}
