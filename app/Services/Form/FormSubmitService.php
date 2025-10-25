<?php

namespace App\Services\Form;

use App\Enums\CustomFieldType;
use App\Models\Employee\Employee;
use App\Models\Form\Form;
use App\Models\Form\Submission;
use App\Models\Form\SubmissionRecord;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FormSubmitService
{
    public function submit(Request $request, Form $form)
    {
        $fields = $form->fields;

        $inputFields = $request->fields ?? [];

        if (auth()->user()->hasRole('student')) {
            $modelType = 'Student';
            $modelId = Student::auth()->first()?->id;
        } elseif (auth()->user()->hasRole('guardian')) {
            $modelType = 'Student';
            // $modelId = Student::auth()->first()?->id;
        } else {
            $modelType = 'Employee';
            $modelId = Employee::auth()->first()?->id;
        }

        if (! $modelId) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        if (Submission::query()
            ->whereFormId($form->id)
            ->whereUserId(auth()->id())
            ->whereModelType($modelType)
            ->whereModelId($modelId)
            ->exists()) {
            throw ValidationException::withMessages(['message' => trans('form.already_submitted')]);
        }

        $formSubmission = Submission::forceCreate([
            'form_id' => $form->id,
            'user_id' => auth()->id(),
            'model_type' => $modelType,
            'model_id' => $modelId,
            'submitted_at' => now()->toDateTimeString(),
        ]);

        foreach ($inputFields as $name => $value) {
            $field = $fields->firstWhere('name', $name);

            if (! $field) {
                continue;
            }

            $submissionRecord = SubmissionRecord::firstOrCreate([
                'submission_id' => $formSubmission->id,
                'field_id' => $field->id,
            ]);

            if ($field->type == CustomFieldType::CAMERA_IMAGE) {
                $this->saveImages($submissionRecord, $value);
            } elseif ($field->type == CustomFieldType::FILE_UPLOAD) {
                // do nothing
            } else {
                $submissionRecord->response = $value;
            }

            $submissionRecord->save();
        }

        $formSubmission->addMedia($request);
    }

    private function saveImages(SubmissionRecord $submissionRecord, array $inputImages = [])
    {
        $images = [];
        foreach ($inputImages as $image) {
            if (Arr::get($image, 'path')) {
                $images[] = Arr::get($image, 'path');

                continue;
            }

            if (empty(Arr::get($image, 'image'))) {
                continue;
            }

            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', Arr::get($image, 'image'));

            $binaryImage = base64_decode($base64Data);

            $storagePath = 'form/';
            $filename = uniqid('image_').'.jpg';
            $images[] = $storagePath.$filename;

            \Storage::disk('public')->put($storagePath.$filename, $binaryImage);
        }

        $submissionRecord->setMeta([
            'images' => $images,
        ]);
        $submissionRecord->save();
    }
}
