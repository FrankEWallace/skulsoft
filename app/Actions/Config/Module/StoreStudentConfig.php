<?php

namespace App\Actions\Config\Module;

use Illuminate\Support\Arr;

class StoreStudentConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'registration_number_prefix' => 'sometimes|max:200',
            'registration_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'registration_number_suffix' => 'sometimes|max:200',
            'enable_provisional_admission' => 'sometimes|boolean',
            'provisional_admission_number_prefix' => 'sometimes|max:200',
            'provisional_admission_number_digit' => 'sometimes|integer|min:0|max:9',
            'provisional_admission_number_suffix' => 'sometimes|max:200',
            'admission_number_prefix' => 'sometimes|max:200',
            'admission_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'admission_number_suffix' => 'sometimes|max:200',
            'transfer_request_number_prefix' => 'sometimes|max:200',
            'transfer_request_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'transfer_request_number_suffix' => 'sometimes|max:200',
            'transfer_number_prefix' => 'sometimes|max:200',
            'transfer_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'transfer_number_suffix' => 'sometimes|max:200',
            'attendance_past_day_limit' => 'sometimes|required|integer|min:0',
            'enable_marital_status' => 'sometimes|boolean',
            'enable_anniversary_date' => 'sometimes|boolean',
            'allow_student_to_submit_contact_edit_request' => 'sometimes|boolean',
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
            'late_fee_waiver_till_date' => 'sometimes|date_format:Y-m-d',
            'allow_flexible_installment_payment' => 'sometimes|boolean',
            'enable_timesheet' => 'sometimes|boolean',
            'allow_student_clock_in_out' => 'sometimes|boolean',
            'enable_qr_code_attendance' => 'sometimes|boolean',
            'has_dynamic_qr_code' => 'sometimes|boolean',
            'qr_code_expiry_duration' => 'sometimes|integer|min:0|max:6000',
            'services' => 'sometimes|array',
            'service_request_number_prefix' => 'sometimes|max:200',
            'service_request_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'service_request_number_suffix' => 'sometimes|max:200',
            'service_request_instruction' => 'nullable|max:1000',
        ], [], [
            'registration_number_prefix' => __('student.registration.config.props.number_prefix'),
            'registration_number_digit' => __('student.registration.config.props.number_digit'),
            'registration_number_suffix' => __('student.registration.config.props.number_suffix'),
            'enable_provisional_admission' => __('student.config.props.enable_provisional_admission'),
            'provisional_admission_number_prefix' => __('student.config.props.provisional_number_prefix'),
            'provisional_admission_number_digit' => __('student.config.props.provisional_number_digit'),
            'provisional_admission_number_suffix' => __('student.config.props.provisional_number_suffix'),
            'admission_number_prefix' => __('student.config.props.number_prefix'),
            'admission_number_digit' => __('student.config.props.number_digit'),
            'admission_number_suffix' => __('student.config.props.number_suffix'),
            'transfer_request_number_prefix' => __('student.transfer_request.config.props.number_prefix'),
            'transfer_request_number_digit' => __('student.transfer_request.config.props.number_digit'),
            'transfer_request_number_suffix' => __('student.transfer_request.config.props.number_suffix'),
            'transfer_number_prefix' => __('student.transfer.config.props.number_prefix'),
            'transfer_number_digit' => __('student.transfer.config.props.number_digit'),
            'transfer_number_suffix' => __('student.transfer.config.props.number_suffix'),
            'attendance_past_day_limit' => __('student.config.props.attendance_past_day_limit'),
            'allow_student_to_submit_contact_edit_request' => __('student.config.props.allow_student_to_submit_contact_edit_request'),
            'enable_marital_status' => __('student.config.props.enable_marital_status'),
            'enable_anniversary_date' => __('student.config.props.enable_anniversary_date'),
            'is_unique_id_number1_enabled' => __('student.config.props.unique_id_number1_enabled'),
            'is_unique_id_number2_enabled' => __('student.config.props.unique_id_number2_enabled'),
            'is_unique_id_number3_enabled' => __('student.config.props.unique_id_number3_enabled'),
            'is_unique_id_number4_enabled' => __('student.config.props.unique_id_number4_enabled'),
            'is_unique_id_number5_enabled' => __('student.config.props.unique_id_number5_enabled'),
            'unique_id_number1_label' => __('student.config.props.unique_id_number1_label'),
            'unique_id_number2_label' => __('student.config.props.unique_id_number2_label'),
            'unique_id_number3_label' => __('student.config.props.unique_id_number3_label'),
            'unique_id_number4_label' => __('student.config.props.unique_id_number4_label'),
            'unique_id_number5_label' => __('student.config.props.unique_id_number5_label'),
            'is_unique_id_number1_required' => __('student.config.props.unique_id_number1_required'),
            'is_unique_id_number2_required' => __('student.config.props.unique_id_number2_required'),
            'is_unique_id_number3_required' => __('student.config.props.unique_id_number3_required'),
            'is_unique_id_number4_required' => __('student.config.props.unique_id_number4_required'),
            'is_unique_id_number5_required' => __('student.config.props.unique_id_number5_required'),
            'late_fee_waiver_till_date' => __('student.config.props.late_fee_waiver_till_date'),
            'allow_flexible_installment_payment' => __('student.config.props.allow_flexible_installment_payment'),
            'enable_timesheet' => __('student.timesheet.timesheet'),
            'allow_student_clock_in_out' => __('student.config.props.allow_student_clock_in_out'),
            'enable_qr_code_attendance' => __('student.config.props.enable_qr_code_attendance'),
            'has_dynamic_qr_code' => __('student.config.props.has_dynamic_qr_code'),
            'qr_code_expiry_duration' => __('student.config.props.qr_code_expiry_duration'),
            'services' => __('student.config.props.available_services'),
            'service_request_number_prefix' => __('student.service_request.config.props.number_prefix'),
            'service_request_number_digit' => __('student.service_request.config.props.number_digit'),
            'service_request_number_suffix' => __('student.service_request.config.props.number_suffix'),
            'service_request_instruction' => __('student.config.props.service_request_instruction'),
        ]);

        if (request()->has('services')) {
            $input['services'] = implode(',', Arr::get($input, 'services', []));
        }

        $input['service_request_instruction'] = clean($input['service_request_instruction']);

        return $input;
    }
}
