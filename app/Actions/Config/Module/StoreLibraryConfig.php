<?php

namespace App\Actions\Config\Module;

class StoreLibraryConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'transaction_number_prefix' => 'sometimes|max:200',
            'transaction_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'transaction_number_suffix' => 'sometimes|max:200',
        ], [], [
            'transaction_number_prefix' => __('library.transaction.config.props.number_prefix'),
            'transaction_number_digit' => __('library.transaction.config.props.number_digit'),
            'transaction_number_suffix' => __('library.transaction.config.props.number_suffix'),
        ]);

        return $input;
    }
}
