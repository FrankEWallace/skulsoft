<?php

namespace App\Actions\Config\Module;

use App\Rules\Latitude;
use App\Rules\Longitude;
use Illuminate\Support\Arr;

class StoreEmployeeConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'code_number_prefix' => 'sometimes|max:100',
            'code_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'code_number_suffix' => 'sometimes|max:100',
            'payroll_number_prefix' => 'sometimes|max:100',
            'payroll_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'payroll_number_suffix' => 'sometimes|max:100',
            'enable_payhead_round_off' => 'sometimes|boolean',
            'show_payroll_as_total_component' => 'sometimes|boolean',
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
            'allow_employee_request_leave_with_exhausted_credit' => 'sometimes|boolean',
            'allow_employee_half_day_leave' => 'sometimes|boolean',
            'attendance_past_day_limit' => 'sometimes|integer|min:0|max:365',
            'allow_employee_clock_in_out' => 'sometimes|boolean',
            'allow_employee_clock_in_out_via_device' => 'sometimes|boolean',
            'enable_qr_code_attendance' => 'sometimes|boolean',
            'has_dynamic_qr_code' => 'sometimes|boolean',
            'qr_code_expiry_duration' => 'sometimes|integer|min:0|max:6000',
            'late_grace_period' => 'sometimes|numeric|min:0|max:60',
            'early_leaving_grace_period' => 'sometimes|numeric|min:0|max:60',
            'present_grace_period' => 'sometimes|numeric|min:0|max:120',
            'enable_geolocation_timesheet' => 'sometimes|boolean',
            'geolocation_latitude' => ['sometimes', 'required_if:enable_geolocation_timesheet,1', new Latitude],
            'geolocation_longitude' => ['sometimes', 'required_if:enable_geolocation_timesheet,1', new Longitude],
            'geolocation_radius' => 'sometimes|required_if:enable_geolocation_timesheet,1|numeric',
            'default_employee_types' => 'sometimes|required|array',
        ], [
            'geolocation_latitude.required_if' => __('validation.required', ['attribute' => __('employee.attendance.config.props.geolocation_latitude')]),
            'geolocation_longitude.required_if' => __('validation.required', ['attribute' => __('employee.attendance.config.props.geolocation_longitude')]),
            'geolocation_radius.required_if' => __('validation.required', ['attribute' => __('employee.attendance.config.props.geolocation_radius')]),
        ], [
            'code_number_prefix' => __('employee.config.props.number_prefix'),
            'code_number_digit' => __('employee.config.props.number_digit'),
            'code_number_suffix' => __('employee.config.props.number_suffix'),
            'payroll_number_prefix' => __('employee.payroll.config.props.number_prefix'),
            'payroll_number_digit' => __('employee.payroll.config.props.number_digit'),
            'payroll_number_suffix' => __('employee.payroll.config.props.number_suffix'),
            'enable_payhead_round_off' => __('employee.payroll.config.props.payhead_round_off'),
            'show_payroll_as_total_component' => __('employee.payroll.config.props.show_payroll_as_total_component'),
            'is_unique_id_number1_enabled' => __('employee.config.props.unique_id_number1_enabled'),
            'is_unique_id_number2_enabled' => __('employee.config.props.unique_id_number2_enabled'),
            'is_unique_id_number3_enabled' => __('employee.config.props.unique_id_number3_enabled'),
            'is_unique_id_number4_enabled' => __('employee.config.props.unique_id_number4_enabled'),
            'is_unique_id_number5_enabled' => __('employee.config.props.unique_id_number5_enabled'),
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
            'allow_employee_request_leave_with_exhausted_credit' => __('employee.leave.config.props.allow_employee_request_leave_with_exhausted_credit'),
            'allow_employee_half_day_leave' => __('employee.leave.config.props.allow_employee_half_day_leave'),
            'attendance_past_day_limit' => __('employee.leave.config.props.attendance_past_day_limit'),
            'allow_employee_clock_in_out' => __('employee.attendance.config.props.allow_employee_clock_in_out'),
            'allow_employee_clock_in_out_via_device' => __('employee.attendance.config.props.allow_employee_clock_in_out_via_device'),
            'enable_qr_code_attendance' => __('employee.attendance.config.props.enable_qr_code_attendance'),
            'has_dynamic_qr_code' => __('employee.attendance.config.props.has_dynamic_qr_code'),
            'late_grace_period' => __('employee.attendance.config.props.late_grace_period'),
            'early_leaving_grace_period' => __('employee.attendance.config.props.early_leaving_grace_period'),
            'present_grace_period' => __('employee.attendance.config.props.present_grace_period'),
            'enable_geolocation_timesheet' => __('employee.attendance.config.props.enable_geolocation_timesheet'),
            'geolocation_latitude' => __('employee.attendance.config.props.geolocation_latitude'),
            'geolocation_longitude' => __('employee.attendance.config.props.geolocation_longitude'),
            'geolocation_radius' => __('employee.attendance.config.props.geolocation_radius'),
            'default_employee_types' => __('employee.config.props.default_employee_types'),
        ]);

        if (request()->has('default_employee_types')) {
            $input['default_employee_types'] = implode(',', Arr::get($input, 'default_employee_types', []));
        }

        return $input;
    }
}
