<?php

namespace App\Services\Form;

use App\Enums\CustomFieldType;
use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Models\Form\Field;
use App\Models\Form\Form;
use App\Support\CleanInput;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FormService
{
    use CleanInput, HasAudience;

    public function preRequisite(Request $request): array
    {
        $customFieldTypes = CustomFieldType::getOptions();

        $studentAudienceTypes = StudentAudienceType::getOptions();

        $employeeAudienceTypes = EmployeeAudienceType::getOptions();

        return compact('customFieldTypes', 'studentAudienceTypes', 'employeeAudienceTypes');
    }

    public function canSubmit(Form $form): bool
    {
        return true;
    }

    public function create(Request $request): Form
    {
        \DB::beginTransaction();

        $form = Form::forceCreate($this->formatParams($request));

        $this->storeAudience($form, $request->all());

        $this->updateFields($request, $form);

        $form->addMedia($request);

        \DB::commit();

        return $form;
    }

    private function formatParams(Request $request, ?Form $form = null): array
    {
        $formatted = [
            'name' => $request->name,
            'due_date' => $request->due_date,
            'summary' => $request->summary,
            'description' => $request->description,
            'audience' => [
                'student_type' => $request->student_audience_type,
                'employee_type' => $request->employee_audience_type,
            ],
        ];

        if (! $form) {
            $formatted['period_id'] = auth()->user()->current_period_id;
            $formatted['user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function updateFields(Request $request, Form $form): void
    {
        $labels = [];

        foreach ($request->fields as $position => $field) {
            $label = Arr::get($field, 'label');
            $name = uniqid();

            if (Arr::get($field, 'type') == CustomFieldType::PARAGRAPH->value) {
                $label = CustomFieldType::PARAGRAPH->value.'_'.$position;
                $field = $this->clean($field, ['content']);
            }

            if (in_array(Arr::get($field, 'type'), [CustomFieldType::CAMERA_IMAGE->value, CustomFieldType::FILE_UPLOAD->value])) {
                $name = Arr::get($field, 'name');
            }

            $formField = Field::firstOrCreate([
                'label' => $label,
                'form_id' => $form->id,
            ]);

            $formField->name = $name;
            $formField->type = Arr::get($field, 'type');
            $formField->position = $position + 1;
            $formField->content = clean(Arr::get($field, 'content'));
            $formField->is_required = (bool) Arr::get($field, 'is_required');
            $formField->setConfig([
                'min_length' => Arr::get($field, 'min_length'),
                'max_length' => Arr::get($field, 'max_length'),
                'min_value' => Arr::get($field, 'min_value'),
                'max_value' => Arr::get($field, 'max_value'),
                'options' => Arr::get($field, 'options', []),
            ]);

            $formField->save();

            $labels[] = $label;
        }

        Field::query()
            ->where('form_id', $form->id)
            ->whereNotIn('label', $labels)
            ->delete();
    }

    public function update(Request $request, Form $form): void
    {
        if ($form->published_at->value) {
            throw ValidationException::withMessages(['message' => trans('form.could_not_modify_after_publish')]);
        }

        \DB::beginTransaction();

        $this->prepareAudienceForUpdate($form, $request->all());

        $form->forceFill($this->formatParams($request, $form))->save();

        $this->updateAudience($form, $request->all());

        $this->updateFields($request, $form);

        $form->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Form $form): void
    {
        if ($form->published_at->value) {
            throw ValidationException::withMessages(['message' => trans('form.could_not_modify_after_publish')]);
        }
    }
}
