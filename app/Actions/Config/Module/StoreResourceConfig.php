<?php

namespace App\Actions\Config\Module;

class StoreResourceConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'enable_filter_by_assigned_subject' => ['boolean'],
            'allow_edit_diary_by_accessible_user' => 'boolean',
            'allow_delete_diary_by_accessible_user' => 'boolean',
            'allow_edit_syllabus_by_accessible_user' => 'boolean',
            'allow_delete_syllabus_by_accessible_user' => 'boolean',
            'allow_edit_lesson_plan_by_accessible_user' => 'boolean',
            'allow_delete_lesson_plan_by_accessible_user' => 'boolean',
            'allow_edit_online_class_by_accessible_user' => 'boolean',
            'allow_delete_online_class_by_accessible_user' => 'boolean',
            'allow_edit_assignment_by_accessible_user' => 'boolean',
            'allow_delete_assignment_by_accessible_user' => 'boolean',
            'allow_edit_learning_material_by_accessible_user' => 'boolean',
            'allow_delete_learning_material_by_accessible_user' => 'boolean',
        ], [], [
            'enable_filter_by_assigned_subject' => __('resource.config.props.filter_by_assigned_subject'),
            'allow_edit_diary_by_accessible_user' => __('resource.config.props.allow_edit_diary_by_accessible_user'),
            'allow_delete_diary_by_accessible_user' => __('resource.config.props.allow_delete_diary_by_accessible_user'),
            'allow_edit_syllabus_by_accessible_user' => __('resource.config.props.allow_edit_syllabus_by_accessible_user'),
            'allow_delete_syllabus_by_accessible_user' => __('resource.config.props.allow_delete_syllabus_by_accessible_user'),
            'allow_edit_lesson_plan_by_accessible_user' => __('resource.config.props.allow_edit_lesson_plan_by_accessible_user'),
            'allow_delete_lesson_plan_by_accessible_user' => __('resource.config.props.allow_delete_lesson_plan_by_accessible_user'),
            'allow_edit_online_class_by_accessible_user' => __('resource.config.props.allow_edit_online_class_by_accessible_user'),
            'allow_delete_online_class_by_accessible_user' => __('resource.config.props.allow_delete_online_class_by_accessible_user'),
            'allow_edit_assignment_by_accessible_user' => __('resource.config.props.allow_edit_assignment_by_accessible_user'),
            'allow_delete_assignment_by_accessible_user' => __('resource.config.props.allow_delete_assignment_by_accessible_user'),
            'allow_edit_learning_material_by_accessible_user' => __('resource.config.props.allow_edit_learning_material_by_accessible_user'),
            'allow_delete_learning_material_by_accessible_user' => __('resource.config.props.allow_delete_learning_material_by_accessible_user'),
        ]);

        return $input;
    }
}
