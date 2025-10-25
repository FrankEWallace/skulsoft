<?php

namespace App\Actions\Config;

class StoreFeatureConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_todo' => 'sometimes|boolean',
            'enable_backup' => 'sometimes|boolean',
            'enable_activity_log' => 'sometimes|boolean',
            'enable_guest_payment' => 'sometimes|boolean',
            'enable_post' => 'sometimes|boolean',
            'guest_payment_instruction' => 'sometimes|max:2000',
            'enable_online_registration' => 'sometimes|boolean',
            'online_registration_instruction' => 'sometimes|max:2000',
            'online_registration_version' => 'sometimes|in:default,minimal|max:255',
            'online_registration_mandatory_upload_field' => 'sometimes|max:255',
            'enable_job_application' => 'sometimes|boolean',
            'job_application_instruction' => 'sometimes|max:2000',
            'enable_transfer_certificate_verification' => 'sometimes|boolean',
            'transfer_certificate_verification_instruction' => 'sometimes|max:2000',
        ], [], [
            'enable_todo' => __('config.feature.props.todo'),
            'enable_backup' => __('config.feature.props.backup'),
            'enable_activity_log' => __('config.feature.props.activity_log'),
            'enable_guest_payment' => __('config.feature.props.guest_payment'),
            'enable_post' => __('config.feature.props.post'),
            'guest_payment_instruction' => __('config.feature.props.guest_payment_instruction'),
            'enable_online_registration' => __('config.feature.props.online_registration'),
            'online_registration_instruction' => __('config.feature.props.online_registration_instruction'),
            'online_registration_version' => __('config.feature.props.online_registration_version'),
            'online_registration_mandatory_upload_field' => __('config.feature.props.online_registration_mandatory_upload_field'),
            'enable_job_application' => __('config.feature.props.job_application'),
            'job_application_instruction' => __('config.feature.props.job_application_instruction'),
            'enable_transfer_certificate_verification' => __('config.feature.props.transfer_certificate_verification'),
            'transfer_certificate_verification_instruction' => __('config.feature.props.transfer_certificate_verification_instruction'),
        ]);

        $input['guest_payment_instruction'] = clean($input['guest_payment_instruction']);
        $input['online_registration_instruction'] = clean($input['online_registration_instruction']);
        $input['job_application_instruction'] = clean($input['job_application_instruction']);
        $input['transfer_certificate_verification_instruction'] = clean($input['transfer_certificate_verification_instruction']);

        return $input;
    }
}
