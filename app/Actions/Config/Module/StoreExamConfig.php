<?php

namespace App\Actions\Config\Module;

class StoreExamConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'marksheet_format' => 'sometimes|string|max:50|in:India,Cameroon,Ghana',
            'enable_auto_lock_marks' => 'sometimes|boolean',
            'auto_lock_marks_period' => 'sometimes|integer|min:1',
        ], [], [
            'marksheet_format' => __('exam.config.props.marksheet_format'),
            'enable_auto_lock_marks' => __('exam.config.props.enable_auto_lock_marks'),
            'auto_lock_marks_period' => __('exam.config.props.auto_lock_marks_period'),
        ]);

        return $input;
    }
}
