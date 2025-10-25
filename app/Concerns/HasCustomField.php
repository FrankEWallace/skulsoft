<?php

namespace App\Concerns;

use App\Enums\CustomFieldType;
use App\Models\CustomField;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasCustomField
{
    public function getCustomFields(string $form): Collection
    {
        return CustomField::query()
            ->byTeam()
            ->whereForm($form)
            ->get();
    }

    public function getCustomFieldsValues(?string $form = null): array
    {
        $form = $form ?? $this->customFieldFormName();
        $customFields = $this->getCustomFields($form);

        $storedCustomFields = $this->getMeta('custom_fields') ?? [];

        return $customFields->map(function ($field) use ($storedCustomFields) {
            $name = Str::camel(Arr::get($field, 'label'));

            $value = collect($storedCustomFields)->firstWhere('uuid', $field['uuid'])['value'] ?? '';

            $formattedValue = $value;
            if (Arr::get($field, 'type') == CustomFieldType::DATE_PICKER) {
                $formattedValue = \Cal::date($value)?->formatted;
            } elseif (Arr::get($field, 'type') == CustomFieldType::TIME_PICKER) {
                $formattedValue = \Cal::time($value)?->formatted;
            } elseif (Arr::get($field, 'type') == CustomFieldType::DATE_TIME_PICKER) {
                $formattedValue = \Cal::dateTime($value)?->formatted;
            } elseif (Arr::get($field, 'type') == CustomFieldType::MULTI_SELECT_INPUT || Arr::get($field, 'type') == CustomFieldType::CHECKBOX_INPUT) {
                if (is_array($value)) {
                    $formattedValue = implode(', ', $value);
                } else {
                    $formattedValue = json_decode($value);
                }
            }

            if (Arr::get($field, 'type') == CustomFieldType::CURRENCY_INPUT) {
                $formattedValue = \Price::from($value)?->formatted;
            }

            return [
                'uuid' => $field['uuid'],
                'label' => $field['label'],
                'name' => $name,
                'value' => $value,
                'formatted_value' => $formattedValue,
            ];
        })->toArray();
    }
}
