<?php

namespace App\Actions\Config\Module;

class StoreContactConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_middle_name_field' => 'sometimes|boolean',
            'enable_third_name_field' => 'sometimes|boolean',
            'is_unique_id_number1_enabled' => 'sometimes|boolean',
            'is_unique_id_number2_enabled' => 'sometimes|boolean',
            'is_unique_id_number3_enabled' => 'sometimes|boolean',
            'is_unique_id_number4_enabled' => 'sometimes|boolean',
            'is_unique_id_number5_enabled' => 'sometimes|boolean',
            'unique_id_number1_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number2_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number3_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number4_label' => 'sometimes|required|min:2|max:100',
            'unique_id_number5_label' => 'sometimes|required|min:2|max:100',
            'is_unique_id_number1_required' => 'sometimes|boolean',
            'is_unique_id_number2_required' => 'sometimes|boolean',
            'is_unique_id_number3_required' => 'sometimes|boolean',
            'is_unique_id_number4_required' => 'sometimes|boolean',
            'is_unique_id_number5_required' => 'sometimes|boolean',
            'enable_locality_field' => 'sometimes|boolean',
            'enable_category_field' => 'sometimes|boolean',
            'enable_caste_field' => 'sometimes|boolean',
        ], [], [
            'enable_middle_name_field' => __('contact.config.props.enable_middle_name_field'),
            'enable_third_name_field' => __('contact.config.props.enable_third_name_field'),
            'is_unique_id_number1_enabled' => __('contact.config.props.is_unique_id_number1_enabled'),
            'is_unique_id_number2_enabled' => __('contact.config.props.is_unique_id_number2_enabled'),
            'is_unique_id_number3_enabled' => __('contact.config.props.is_unique_id_number3_enabled'),
            'is_unique_id_number4_enabled' => __('contact.config.props.is_unique_id_number4_enabled'),
            'is_unique_id_number5_enabled' => __('contact.config.props.is_unique_id_number5_enabled'),
            'unique_id_number1_label' => __('employee.config.props.unique_id_number1_label'),
            'unique_id_number2_label' => __('employee.config.props.unique_id_number2_label'),
            'unique_id_number3_label' => __('employee.config.props.unique_id_number3_label'),
            'unique_id_number4_label' => __('employee.config.props.unique_id_number4_label'),
            'unique_id_number5_label' => __('employee.config.props.unique_id_number5_label'),
            'is_unique_id_number1_required' => __('employee.config.props.unique_id_number1_required'),
            'is_unique_id_number2_required' => __('employee.config.props.unique_id_number2_required'),
            'is_unique_id_number3_required' => __('employee.config.props.unique_id_number3_required'),
            'is_unique_id_number4_required' => __('employee.config.props.unique_id_number4_required'),
            'is_unique_id_number5_required' => __('employee.config.props.unique_id_number5_required'),
            'enable_locality_field' => __('contact.props.locality'),
            'enable_category_field' => __('contact.category.category'),
            'enable_caste_field' => __('contact.caste.caste'),
        ]);

        return $input;
    }
}
