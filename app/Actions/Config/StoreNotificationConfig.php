<?php

namespace App\Actions\Config;

class StoreNotificationConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_mail_notification' => 'sometimes|boolean',
            'enable_mobile_push_notification' => 'sometimes|boolean',
            'enable_guest_notification_bar' => 'sometimes|boolean',
            'enable_app_notification_bar' => 'sometimes|boolean',
            'guest_notification_message' => 'required_if:enable_guest_notification_bar,true|max:1000',
            'app_notification_message' => 'required_if:enable_app_notification_bar,true|max:1000',
            'enable_pusher_notification' => 'sometimes|boolean',
            'pusher_app_id' => 'required_if:enable_pusher_notification,true|alpha_num',
            'pusher_app_key' => 'required_if:enable_pusher_notification,true|alpha_num',
            'pusher_app_secret' => 'required_if:enable_pusher_notification,true|alpha_num',
            'pusher_app_cluster' => 'required_if:enable_pusher_notification,true|alpha_num',
        ], [
            'guest_notification_message.required_if' => trans('validation.required'),
            'app_notification_message.required_if' => trans('validation.required'),
            'pusher_app_id.required_if' => trans('validation.required'),
            'pusher_app_key.required_if' => trans('validation.required'),
            'pusher_app_secret.required_if' => trans('validation.required'),
            'pusher_app_cluster.required_if' => trans('validation.required'),
        ], [
            'enable_mail_notification' => __('config.notification.props.mail_notification'),
            'enable_mobile_push_notification' => __('config.notification.props.mobile_push_notification'),
            'enable_guest_notification_bar' => __('config.notification.props.enable_guest_notification_bar'),
            'enable_app_notification_bar' => __('config.notification.props.enable_app_notification_bar'),
            'app_notification_message' => __('config.notification.props.app_notification_message'),
            'guest_notification_message' => __('config.notification.props.guest_notification_message'),
            'enable_pusher_notification' => __('config.notification.props.enable_pusher_notification'),
            'pusher_app_id' => __('config.notification.props.pusher_app_id'),
            'pusher_app_key' => __('config.notification.props.pusher_app_key'),
            'pusher_app_secret' => __('config.notification.props.pusher_app_secret'),
            'pusher_app_cluster' => __('config.notification.props.pusher_app_cluster'),
        ]);

        return $input;
    }
}
