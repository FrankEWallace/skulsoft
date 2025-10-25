<?php

namespace App\Actions\Config\Module;

class StoreAcademicConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'period_selection' => ['required', 'string', 'in:period_wise,session_wise'],
            'allow_listing_subject_wise_student' => ['boolean'],
        ], [], [
            'period_selection' => trans('academic.period_selection'),
            'allow_listing_subject_wise_student' => trans('academic.config.props.allow_listing_subject_wise_student'),
        ]);

        return $input;
    }
}
