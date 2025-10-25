<?php

namespace App\Services\Student;

use App\Http\Resources\MediaResource;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransferCertificateService
{
    public function preRequisite()
    {
        $instruction = nl2br(config('config.feature.transfer_certificate_verification_instruction'));

        return compact('instruction');
    }

    public function getStudent(Request $request)
    {
        $request->validate([
            'certificate_number' => 'required|max:50',
            'code_number' => 'required|max:50',
        ]);

        $student = Student::query()
            ->whereHas('admission', function ($query) use ($request) {
                $query->where('code_number', $request->code_number);
            })
            ->where('meta->transfer_certificate_number', $request->certificate_number)
            ->first();

        if (! $student) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        return $student;
    }

    public function verify(Request $request)
    {
        $student = $this->getStudent($request);

        $student->load('contact', 'batch.course', 'admission.media');

        return [
            'uuid' => $student->uuid,
            'name' => $student->contact->name,
            'code_number' => $student->code_number,
            'certificate_number' => $student->getMeta('transfer_certificate_number'),
            'course' => $student->batch->course->name,
            'batch' => $student->batch->name,
            'leaving_date' => $student->admission->leaving_date->formatted,
            'media' => MediaResource::collection($student->admission->media),
        ];
    }
}
