<?php

namespace App\Actions\Config\Module;

class StoreReceptionConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enquiry_number_prefix' => 'sometimes|max:200',
            'enquiry_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'enquiry_number_suffix' => 'sometimes|max:200',
            'visitor_log_number_prefix' => 'sometimes|max:200',
            'visitor_log_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'visitor_log_number_suffix' => 'sometimes|max:200',
            'gate_pass_number_prefix' => 'sometimes|max:200',
            'gate_pass_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'gate_pass_number_suffix' => 'sometimes|max:200',
            'complaint_number_prefix' => 'sometimes|max:200',
            'complaint_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'complaint_number_suffix' => 'sometimes|max:200',
            'query_number_prefix' => 'sometimes|max:200',
            'query_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'query_number_suffix' => 'sometimes|max:200',
        ], [], [
            'enquiry_number_prefix' => __('reception.enquiry.config.props.number_prefix'),
            'enquiry_number_digit' => __('reception.enquiry.config.props.number_digit'),
            'enquiry_number_suffix' => __('reception.enquiry.config.props.number_suffix'),
            'visitor_log_number_prefix' => __('reception.visitor_log.config.props.number_prefix'),
            'visitor_log_number_digit' => __('reception.visitor_log.config.props.number_digit'),
            'visitor_log_number_suffix' => __('reception.visitor_log.config.props.number_suffix'),
            'gate_pass_number_prefix' => __('reception.gate_pass.config.props.number_prefix'),
            'gate_pass_number_digit' => __('reception.gate_pass.config.props.number_digit'),
            'gate_pass_number_suffix' => __('reception.gate_pass.config.props.number_suffix'),
            'complaint_number_prefix' => __('reception.complaint.config.props.number_prefix'),
            'complaint_number_digit' => __('reception.complaint.config.props.number_digit'),
            'complaint_number_suffix' => __('reception.complaint.config.props.number_suffix'),
            'query_number_prefix' => __('reception.query.config.props.number_prefix'),
            'query_number_digit' => __('reception.query.config.props.number_digit'),
            'query_number_suffix' => __('reception.query.config.props.number_suffix'),
        ]);

        return $input;
    }
}
