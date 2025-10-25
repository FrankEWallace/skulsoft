<?php

namespace App\Concerns;

use App\Enums\CustomFieldType;
use App\Helpers\CalHelper;
use Illuminate\Support\Arr;

trait CustomFieldValidation
{
    public function validateFields($validator, $fields, $fieldPropName = 'custom_fields'): array
    {
        $customFields = [];
        foreach ($fields as $index => $customField) {
            $field = Arr::get($customField, 'name');
            $fieldLabel = Arr::get($customField, 'label');

            $inputField = collect($this->$fieldPropName ?? [])->firstWhere('name', $field);
            $value = Arr::get($inputField, 'value');

            if (Arr::get($inputField, 'type') == CustomFieldType::PARAGRAPH->value) {
                continue;
            }

            $errorField = $fieldPropName.'.'.$field;

            if (Arr::get($customField, 'is_required') && ! $value) {
                $validator->errors()->add($errorField, __('validation.required', ['attribute' => $fieldLabel]));
            }

            if (in_array(Arr::get($customField, 'type'), [CustomFieldType::TEXT_INPUT->value, CustomFieldType::MULTI_LINE_TEXT_INPUT->value]) && strlen($value) < Arr::get($customField, 'min_length')) {
                $validator->errors()->add($errorField, __('validation.min.string', ['attribute' => $fieldLabel, 'min' => Arr::get($customField, 'min_length')]));
            }

            if (in_array(Arr::get($customField, 'type'), [CustomFieldType::EMAIL_INPUT->value]) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $validator->errors()->add($errorField, __('validation.email', ['attribute' => $fieldLabel]));
            }

            if (in_array(Arr::get($customField, 'type'), [CustomFieldType::TEXT_INPUT->value, CustomFieldType::MULTI_LINE_TEXT_INPUT->value]) && strlen($value) > Arr::get($customField, 'max_length')) {
                $validator->errors()->add($errorField, __('validation.max.string', ['attribute' => $fieldLabel, 'max' => Arr::get($customField, 'max_length')]));
            }

            if (in_array(Arr::get($customField, 'type'), [CustomFieldType::NUMBER_INPUT->value, CustomFieldType::CURRENCY_INPUT->value]) && $value < Arr::get($customField, 'min_value')) {
                $validator->errors()->add($errorField, __('validation.min.numeric', ['attribute' => $fieldLabel, 'min' => Arr::get($customField, 'min_value')]));
            }

            if (in_array(Arr::get($customField, 'type'), [CustomFieldType::NUMBER_INPUT->value, CustomFieldType::CURRENCY_INPUT->value]) && $value > Arr::get($customField, 'max_value')) {
                $validator->errors()->add($errorField, __('validation.max.numeric', ['attribute' => $fieldLabel, 'max' => Arr::get($customField, 'max_value')]));
            }

            if ($value && Arr::get($customField, 'type') == 'date_picker' && ! CalHelper::validateDate($value)) {
                $validator->errors()->add($errorField, __('validation.date', ['attribute' => $fieldLabel]));
            }

            if ($value && Arr::get($customField, 'type') == 'time_picker' && ! CalHelper::validateDateFormat($value, 'H:i:s')) {
                $validator->errors()->add($errorField, __('validation.date', ['attribute' => $fieldLabel]));
            }

            if ($value && Arr::get($customField, 'type') == 'date_time_picker' && ! CalHelper::validateDateFormat($value, 'Y-m-d H:i:s')) {
                $validator->errors()->add($errorField, __('validation.date', ['attribute' => $fieldLabel]));
            }

            // if ($value && Arr::get($customField, 'type') == 'camera_image') {
            //     foreach ($value as $image) {
            //          Validate base64 image
            //     }
            // }

            $customFields[$field] = $value;
        }

        return $customFields;
    }
}
