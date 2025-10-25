<?php

namespace App\Http\Requests\Student;

use App\Enums\Student\TransferRequestStatus;
use App\Models\Media;
use App\Models\Student\Student;
use App\Models\Student\TransferRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TransferRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'student' => 'required|uuid',
            'request_date' => 'nullable|date_format:Y-m-d',
            'transfer_certificate_number' => 'nullable',
            'reason' => 'required|max:500',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('transfer_request');

            $mediaModel = (new TransferRequest)->getModelName();

            $student = Student::query()
                ->byPeriod()
                ->summary()
                ->filterAccessible()
                ->where('students.uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student');

            $requestDate = $this->request_date ?: today()->toDateString();

            if ($student->joining_date > $requestDate) {
                throw ValidationException::withMessages(['date' => trans('student.transfer_request.date_before_joining', ['attribute' => \Cal::date($student->joining_date)->formatted])]);
            }

            if ($student->start_date->value > $requestDate) {
                throw ValidationException::withMessages(['date' => trans('student.transfer.date_before_promotion', ['attribute' => $student->start_date->formatted])]);
            }

            if ($student->leaving_date) {
                throw ValidationException::withMessages(['student' => trans('student.transfer.already_transferred')]);
            }

            $existingTransferRequest = TransferRequest::query()
                ->where('student_id', $student->id)
                ->whereIn('status', [TransferRequestStatus::REQUESTED, TransferRequestStatus::IN_PROGRESS])
                ->where('uuid', '!=', $uuid)
                ->exists();

            if ($existingTransferRequest) {
                throw ValidationException::withMessages(['student' => trans('student.transfer_request.duplicate_request')]);
            }

            $fee = $student->getFeeSummary();

            $total = Arr::get($fee, 'total_fee');
            $paid = Arr::get($fee, 'paid_fee');
            $balance = Arr::get($fee, 'balance_fee');

            if ($balance->value > 0) {
                throw ValidationException::withMessages(['message' => trans('student.transfer_request.fee_due', ['total' => $total->formatted, 'paid' => $paid->formatted])]);
            }

            if (auth()->user()->is_student_or_guardian) {
                $attachedMedia = Media::whereModelType($mediaModel)
                    ->whereToken($this->media_token)
                    // ->where('meta->hash', $this->media_hash)
                    ->where('meta->is_temp_deleted', false)
                    ->where('meta->section', 'application')
                    ->where(function ($q) use ($uuid) {
                        $q->whereStatus(0)
                            ->when($uuid, function ($q) {
                                $q->orWhere('status', 1);
                            });
                    })
                    ->exists();

                if (! $attachedMedia) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
                }
            }

            $this->merge([
                'student' => $student,
                'request_date' => $requestDate,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'student' => trans('student.student'),
            'reason' => trans('student.transfer_request.props.reason'),
            'request_date' => trans('student.transfer_request.props.request_date'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
        ];
    }
}
