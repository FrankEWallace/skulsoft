<?php

namespace App\Concerns;

use App\Enums\CustomFieldType;
use App\Helpers\CalHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait CustomFormFieldValidation
{
    public function validateFields(mixed $validator, Collection $customFields, array $data)
    {
        $input = collect($data);

        $newCustomFields = [];
        foreach ($customFields as $index => $customField) {
            $name = Str::camel($customField->label);
            $minLength = $customField->getConfig('min_length');
            $maxLength = $customField->getConfig('max_length');
            $minValue = $customField->getConfig('min_value');
            $maxValue = $customField->getConfig('max_value');
            $options = collect(explode(',', $customField->getConfig('options')))->map(fn ($option) => trim($option))->toArray();
            $value = Arr::get($input->firstWhere('name', $name) ?? [], 'value');

            if (! $value && $customField->is_required) {
                $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.required', ['attribute' => $customField->label]));
            }

            if (empty($value)) {
                continue;
            }

            if ($customField->type->value == CustomFieldType::TEXT_INPUT->value || $customField->type->value == CustomFieldType::MULTI_LINE_TEXT_INPUT->value) {
                if (! empty($minLength) && ! empty($maxLength)) {
                    if (strlen($value) < $minLength || strlen($value) > $maxLength) {
                        $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.between.numeric', ['attribute' => $customField->label, 'min' => $minLength, 'max' => $maxLength]));
                    }
                }
            } elseif ($customField->type->value == CustomFieldType::EMAIL_INPUT->value) {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.email', ['attribute' => $customField->label]));
                }
            } elseif ($customField->type->value == CustomFieldType::NUMBER_INPUT->value || $customField->type->value == CustomFieldType::CURRENCY_INPUT->value) {
                if (! is_numeric($value)) {
                    $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.numeric', ['attribute' => $customField->label]));
                }

                if (! empty($minValue) && ! empty($maxValue)) {
                    if ($value < $minValue || $value > $maxValue) {
                        $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.between.numeric', ['attribute' => $customField->label, 'min' => $minValue, 'max' => $maxValue]));
                    }
                }
            } elseif ($customField->type->value == CustomFieldType::DATE_PICKER->value && ! CalHelper::validateDate($value)) {
                $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.date', ['attribute' => $customField->label]));
            } elseif ($customField->type->value == CustomFieldType::TIME_PICKER->value && ! CalHelper::validateDateFormat($value, 'H:i:s')) {
                $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.date', ['attribute' => $customField->label]));
            } elseif ($customField->type->value == CustomFieldType::DATE_TIME_PICKER->value && ! CalHelper::validateDateFormat($value, 'Y-m-d H:i:s')) {
                $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.date', ['attribute' => $customField->label]));
            } elseif ($customField->type->value == CustomFieldType::SELECT_INPUT->value || $customField->type->value == CustomFieldType::RADIO_INPUT->value) {
                if (! in_array($value, $options)) {
                    $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.in', ['attribute' => $customField->label, 'values' => implode(', ', $options)]));
                }
            } elseif ($customField->type->value == CustomFieldType::MULTI_SELECT_INPUT->value || $customField->type->value == CustomFieldType::CHECKBOX_INPUT->value) {
                foreach ($value as $result) {
                    if (! in_array($result, $options)) {
                        $validator->errors()->add('custom_fields.'.$index.'.'.$name, trans('validation.in', ['attribute' => $customField->label, 'values' => implode(', ', $options)]));
                    }
                }
            }

            $newCustomFields[] = [
                'uuid' => $customField->uuid,
                'value' => $value,
            ];
        }

        return $newCustomFields;
    }
}
