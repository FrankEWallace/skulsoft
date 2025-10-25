<?php

namespace App\Actions\Config\Module;

class StoreTransportConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'show_transport_route_in_dashboard' => 'boolean',
        ], [
        ], [
            'show_transport_route_in_dashboard' => __('transport.config.props.route_in_dashboard'),
        ]);

        return $input;
    }
}
