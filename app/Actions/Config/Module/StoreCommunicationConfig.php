<?php

namespace App\Actions\Config\Module;

class StoreCommunicationConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'announcement_number_prefix' => 'sometimes|max:200',
            'announcement_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'announcement_number_suffix' => 'sometimes|max:200',
            'visitor_log_number_prefix' => 'sometimes|max:200',
            'visitor_log_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'visitor_log_number_suffix' => 'sometimes|max:200',
            'gate_pass_number_prefix' => 'sometimes|max:200',
            'gate_pass_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'gate_pass_number_suffix' => 'sometimes|max:200',
        ], [], [
            'announcement_number_prefix' => __('communication.announcement.config.props.number_prefix'),
            'announcement_number_digit' => __('communication.announcement.config.props.number_digit'),
            'announcement_number_suffix' => __('communication.announcement.config.props.number_suffix'),
        ]);

        return $input;
    }
}
