<?php

namespace App\Actions\Config\Module;

class StoreMessConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'show_mess_schedule_in_dashboard' => 'boolean',
        ], [
        ], [
            'show_mess_schedule_in_dashboard' => __('mess.config.props.schedule_in_dashboard'),
        ]);

        return $input;
    }
}
