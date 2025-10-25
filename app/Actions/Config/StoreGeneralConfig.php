<?php

namespace App\Actions\Config;

class StoreGeneralConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'type' => 'required|in:general',
            'app_name' => 'sometimes|required',
            'app_description' => 'nullable',
            'meta_author' => 'string|max:255',
            'meta_description' => 'string|max:255',
            'meta_keywords' => 'string|max:255',
            'app_country' => 'sometimes|required',
            'app_email' => 'sometimes|required|email',
            'app_phone' => 'nullable',
            'app_fax' => 'nullable',
            'app_website' => 'nullable',
            'app_address_line1' => 'nullable',
            'app_address_line2' => 'nullable',
            'app_city' => 'nullable',
            'app_state' => 'nullable',
            'app_zipcode' => 'nullable',
            'financial_year_code' => 'nullable',
        ], [], [
            'app_name' => __('config.general.props.app_name'),
            'app_description' => __('config.general.props.app_description'),
            'meta_author' => __('config.general.props.meta_author'),
            'meta_description' => __('config.general.props.meta_description'),
            'meta_keywords' => __('config.general.props.meta_keywords'),
            'app_country' => __('config.general.props.app_country'),
            'app_email' => __('config.general.props.app_email'),
            'app_phone' => __('config.general.props.app_phone'),
            'app_fax' => __('config.general.props.app_fax'),
            'app_website' => __('config.general.props.app_website'),
            'app_address_line1' => __('config.general.props.app_address_line1'),
            'app_address_line2' => __('config.general.props.app_address_line2'),
            'app_city' => __('config.general.props.app_city'),
            'app_state' => __('config.general.props.app_state'),
            'app_zipcode' => __('config.general.props.app_zipcode'),
            'financial_year_code' => __('config.general.props.financial_year_code'),
        ]);

        return $input;
    }
}
