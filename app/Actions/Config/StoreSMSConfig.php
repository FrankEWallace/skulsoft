<?php

namespace App\Actions\Config;

use App\Helpers\ListHelper;

class StoreSMSConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'driver' => 'required|in:'.implode(',', ListHelper::getListKey('sms_drivers')),
            'sender_id' => 'sometimes|required',
            'test_number' => 'sometimes|required',
            'test_template_id' => 'sometimes|required_if:driver,msg91',
            'api_key' => 'sometimes|required_if:driver,twilio',
            'api_secret' => 'sometimes|required_if:driver,twilio',
            'api_url' => 'sometimes|required_if:driver,custom',
            'api_method' => 'sometimes|required_if:driver,custom|in:GET,POST',
            'number_prefix' => 'sometimes|nullable',
            'sender_id_param' => 'sometimes|required_if:driver,custom',
            'receiver_param' => 'sometimes|required_if:driver,custom',
            'message_param' => 'sometimes|required_if:driver,custom',
            'template_id_param' => 'sometimes',
            'additional_params' => 'sometimes',
            'api_headers' => 'sometimes',
        ], [
            'api_url.required_if' => __('validation.required', ['attribute' => __('config.sms.props.api_url')]),
            'number_prefix.required_if' => __('validation.required', ['attribute' => __('config.sms.props.number_prefix')]),
            'api_method.required_if' => __('validation.required', ['attribute' => __('config.sms.props.api_method')]),
            'sender_id_param.required_if' => __('validation.required', ['attribute' => __('config.sms.props.sender_id_param')]),
            'receiver_param.required_if' => __('validation.required', ['attribute' => __('config.sms.props.receiver_param')]),
            'message_param.required_if' => __('validation.required', ['attribute' => __('config.sms.props.message_param')]),
            'test_template_id.required_if' => __('validation.required', ['attribute' => __('config.sms.template.props.template_id')]),
        ], [
            'driver' => __('config.sms.props.driver'),
            'sender_id' => __('config.sms.props.sender_id'),
            'test_number' => __('config.sms.props.test_number'),
            'api_key' => __('config.sms.props.api_key'),
            'api_secret' => __('config.sms.props.api_secret'),
            'api_url' => __('config.sms.props.api_url'),
            'api_method' => __('config.sms.props.api_method'),
            'number_prefix' => __('config.sms.props.number_prefix'),
            'sender_id_param' => __('config.sms.props.sender_id_param'),
            'receiver_param' => __('config.sms.props.receiver_param'),
            'message_param' => __('config.sms.props.message_param'),
            'template_id_param' => __('config.sms.props.template_id_param'),
            'additional_params' => __('config.sms.props.additional_params'),
            'api_headers' => __('config.sms.props.api_headers'),
        ]);

        return $input;
    }
}
