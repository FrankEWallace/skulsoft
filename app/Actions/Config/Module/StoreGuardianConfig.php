<?php

namespace App\Actions\Config\Module;

class StoreGuardianConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'unique_id_number1_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number2_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number3_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number4_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number5_label' => 'sometimes|required|min:2|max:100',
            'is_unique_id_number1_enabled' => 'sometimes|boolean',
            'is_unique_id_number2_enabled' => 'sometimes|boolean',
            'is_unique_id_number3_enabled' => 'sometimes|boolean',
            'is_unique_id_number4_enabled' => 'sometimes|boolean',
            'is_unique_id_number5_enabled' => 'sometimes|boolean',
            'is_unique_id_number1_required' => 'sometimes|boolean',
            'is_unique_id_number2_required' => 'sometimes|boolean',
            'is_unique_id_number3_required' => 'sometimes|boolean',
            'is_unique_id_number4_required' => 'sometimes|boolean',
            'is_unique_id_number5_required' => 'sometimes|boolean',
        ], [], [
            'is_unique_id_number1_enabled' => __('guardian.config.props.unique_id_number1_enabled'),
            'is_unique_id_number2_enabled' => __('guardian.config.props.unique_id_number2_enabled'),
            'is_unique_id_number3_enabled' => __('guardian.config.props.unique_id_number3_enabled'),
            'is_unique_id_number4_enabled' => __('guardian.config.props.unique_id_number4_enabled'),
            'is_unique_id_number5_enabled' => __('guardian.config.props.unique_id_number5_enabled'),
            'unique_id_number1_label' => __('guardian.config.props.unique_id_number1_label'),
            'unique_id_number2_label' => __('guardian.config.props.unique_id_number2_label'),
            'unique_id_number3_label' => __('guardian.config.props.unique_id_number3_label'),
            'unique_id_number4_label' => __('guardian.config.props.unique_id_number4_label'),
            'unique_id_number5_label' => __('guardian.config.props.unique_id_number5_label'),
            'is_unique_id_number1_required' => __('guardian.config.props.unique_id_number1_required'),
            'is_unique_id_number2_required' => __('guardian.config.props.unique_id_number2_required'),
            'is_unique_id_number3_required' => __('guardian.config.props.unique_id_number3_required'),
            'is_unique_id_number4_required' => __('guardian.config.props.unique_id_number4_required'),
            'is_unique_id_number5_required' => __('guardian.config.props.unique_id_number5_required'),
        ]);

        return $input;
    }
}
