<?php

namespace App\Actions\Config;

use App\Helpers\ListHelper;

class StoreWhatsAppConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'provider' => 'required|in:'.implode(',', ListHelper::getListKey('whatsapp_providers')),
            'sender_id' => 'sometimes|required',
            'test_number' => 'sometimes|required',
            'test_template_id' => 'sometimes|required',
            'account_id' => 'sometimes|required_if:provider,pinnacle',
            'api_key' => 'sometimes|required_if:provider,pinnacle,msg91',
            'api_id' => 'sometimes|required_if:provider,isms_my',
            'api_secret' => 'sometimes|required_if:provider,isms_my',
            'username' => 'sometimes|required_if:provider,isms_my',
            'password' => 'sometimes|required_if:provider,isms_my',
            'api_url' => 'sometimes|required_if:provider,custom',
            'api_method' => 'sometimes|required_if:provider,custom|in:GET,POST',
            'number_prefix' => 'sometimes|nullable',
            'sender_id_param' => 'sometimes|required_if:provider,custom',
            'receiver_param' => 'sometimes|required_if:provider,custom',
            'message_param' => 'sometimes|required_if:provider,custom',
            'template_id_param' => 'sometimes',
            'template_variable_param' => 'sometimes',
            'additional_params' => 'sometimes',
            'api_headers' => 'sometimes',
        ], [
            'api_key.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.api_key')]),
            'api_id.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.api_id')]),
            'api_secret.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.api_secret')]),
            'username.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.username')]),
            'password.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.password')]),
            'api_url.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.api_url')]),
            'number_prefix.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.number_prefix')]),
            'api_method.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.api_method')]),
            'sender_id_param.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.sender_id_param')]),
            'receiver_param.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.receiver_param')]),
            'message_param.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.props.message_param')]),
            'test_template_id.required_if' => __('validation.required', ['attribute' => __('config.whatsapp.template.props.template_id')]),
        ], [
            'provider' => __('config.whatsapp.props.provider'),
            'sender_id' => __('config.whatsapp.props.sender_id'),
            'test_number' => __('config.whatsapp.props.test_number'),
            'test_template_id' => __('config.whatsapp.props.test_template_id'),
            'account_id' => __('config.whatsapp.props.account_id'),
            'api_key' => __('config.whatsapp.props.api_key'),
            'api_id' => __('config.whatsapp.props.api_id'),
            'api_secret' => __('config.whatsapp.props.api_secret'),
            'username' => __('config.whatsapp.props.username'),
            'password' => __('config.whatsapp.props.password'),
            'api_url' => __('config.whatsapp.props.api_url'),
            'api_method' => __('config.whatsapp.props.api_method'),
            'number_prefix' => __('config.whatsapp.props.number_prefix'),
            'sender_id_param' => __('config.whatsapp.props.sender_id_param'),
            'receiver_param' => __('config.whatsapp.props.receiver_param'),
            'message_param' => __('config.whatsapp.props.message_param'),
            'template_id_param' => __('config.whatsapp.props.template_id_param'),
            'template_variable_param' => __('config.whatsapp.props.template_variable_param'),
            'additional_params' => __('config.whatsapp.props.additional_params'),
            'api_headers' => __('config.whatsapp.props.api_headers'),
        ]);

        return $input;
    }
}
