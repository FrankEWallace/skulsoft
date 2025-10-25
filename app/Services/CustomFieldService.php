<?php

namespace App\Services;

use App\Enums\CustomFieldForm;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldService
{
    public function preRequisite(Request $request): array
    {
        $forms = CustomFieldForm::getOptions();

        $types = CustomFieldType::getOptions(except: ['paragraph', 'file_upload', 'camera_image']);

        return compact('forms', 'types');
    }

    public function create(Request $request): CustomField
    {
        \DB::beginTransaction();

        $customField = CustomField::forceCreate($this->formatParams($request));

        \DB::commit();

        return $customField;
    }

    private function formatParams(Request $request, ?CustomField $customField = null): array
    {
        $customFieldatted = [
            'label' => $request->label,
            'type' => $request->type,
            'form' => $request->form,
            'is_required' => $request->boolean('is_required'),
        ];

        $config = $customField?->config ?? [];

        if (in_array($request->type, ['text_input', 'multi_line_text_input'])) {
            $config['min_length'] = $request->min_length;
            $config['max_length'] = $request->max_length;
        }

        if (in_array($request->type, ['number_input', 'currency_input'])) {
            $config['min_value'] = $request->min_value;
            $config['max_value'] = $request->max_value;
        }

        if (in_array($request->type, ['select_input', 'multi_select_input', 'radio_input', 'checkbox_input'])) {
            $config['options'] = $request->options;
        }

        if (! $customField) {
            $customFieldatted['position'] = $request->integer('position', 0);
            $customFieldatted['team_id'] = auth()->user()->current_team_id;
        }

        $customFieldatted['config'] = $config;

        return $customFieldatted;
    }

    public function update(Request $request, CustomField $customField): void
    {
        \DB::beginTransaction();

        $customField->forceFill($this->formatParams($request, $customField))->save();

        \DB::commit();
    }

    public function deletable(CustomField $customField): void {}
}
