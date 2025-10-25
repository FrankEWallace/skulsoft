<?php

namespace App\Services\Student;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Http\Resources\Student\StudentResource;
use App\Models\Academic\Batch;
use App\Models\HealthRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class HealthRecordService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    private function validateInput(Request $request): Batch
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'batch' => 'required|uuid',
        ]);

        return Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');
    }

    public function fetch(Request $request)
    {
        $batch = $this->validateInput($request);

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all());

        $healthRecords = HealthRecord::query()
            ->where('date', $request->date)
            ->where('model_type', 'Student')
            ->whereIn('model_id', Arr::pluck($students, 'id'))
            ->get();

        $students->each(function ($student) use ($healthRecords) {
            $student->has_health_record = true;
            $healthRecord = $healthRecords->where('model_id', $student->id)->first();

            $student->height = Arr::get($healthRecord?->details, 'general.height');
            $student->weight = Arr::get($healthRecord?->details, 'general.weight');
            $student->chest = Arr::get($healthRecord?->details, 'general.chest');
            $student->left_eye = Arr::get($healthRecord?->details, 'vision.left_eye');
            $student->right_eye = Arr::get($healthRecord?->details, 'vision.right_eye');
            $student->dental_hygiene = Arr::get($healthRecord?->details, 'dental.dental_hygiene');
        });

        return StudentResource::collection($students)
            ->additional([
                'meta' => [
                    'has_health_record' => true,
                ],
            ]);
    }

    public function store(Request $request)
    {
        $batch = $this->validateInput($request);

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

        if (array_diff(Arr::pluck($request->students, 'uuid'), Arr::pluck($students, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        foreach ($request->students as $index => $input) {
            $student = Arr::first($students, fn ($student) => $student['uuid'] == $input['uuid']);

            $healthRecord = HealthRecord::query()
                ->firstOrCreate([
                    'date' => $request->date,
                    'model_type' => 'Student',
                    'model_id' => Arr::get($student, 'id'),
                ]);

            $details = $healthRecord->details ?? [];
            $details['general'] = [
                'height' => Arr::get($input, 'height'),
                'weight' => Arr::get($input, 'weight'),
                'chest' => Arr::get($input, 'chest'),
            ];
            $details['vision'] = [
                'left_eye' => Arr::get($input, 'left_eye'),
                'right_eye' => Arr::get($input, 'right_eye'),
            ];
            $details['dental'] = [
                'dental_hygiene' => Arr::get($input, 'dental_hygiene'),
            ];

            $healthRecord->details = $details;
            $healthRecord->save();
        }
    }
}
