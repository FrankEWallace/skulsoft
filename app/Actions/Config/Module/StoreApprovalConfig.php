<?php

namespace App\Actions\Config\Module;

class StoreApprovalConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'request_number_prefix' => 'sometimes|max:200',
            'request_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'request_number_suffix' => 'sometimes|max:200',
        ], [], [
            'request_number_prefix' => __('approval.config.props.request_number_prefix'),
            'request_number_digit' => __('approval.config.props.request_number_digit'),
            'request_number_suffix' => __('approval.config.props.request_number_suffix'),
        ]);

        return $input;
    }
}
