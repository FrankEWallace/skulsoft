<?php

namespace App\Actions\Config\Module;

class StoreRecruitmentConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'vacancy_number_prefix' => 'sometimes|max:200',
            'vacancy_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'vacancy_number_suffix' => 'sometimes|max:200',
        ], [], [
            'vacancy_number_prefix' => __('recruitment.config.props.vacancy_number_prefix'),
            'vacancy_number_digit' => __('recruitment.config.props.vacancy_number_digit'),
            'vacancy_number_suffix' => __('recruitment.config.props.vacancy_number_suffix'),
        ]);

        return $input;
    }
}
