<?php

namespace App\Http\Requests\Student;

use App\Enums\Academic\CertificateType;
use App\Enums\OptionType;
use App\Enums\Student\TransferRequestStatus;
use App\Models\Academic\Certificate;
use App\Models\Option;
use App\Models\Student\Student;
use App\Models\Student\TransferRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class TransferRequestActionRequest extends FormRequest
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
        $rules = [
            'status' => ['required', new Enum(TransferRequestStatus::class)],
            'comment' => 'required|min:5|max:1000',
        ];

        if ($this->status == TransferRequestStatus::APPROVED->value) {
            $rules['transfer_certificate_number'] = 'required';
            $rules['transfer_date'] = 'required|date_format:Y-m-d';
            $rules['reason'] = 'required|uuid';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('transfer_request');

            $transferRequest = TransferRequest::findByUuidOrFail($uuid);

            if ($transferRequest->status->value == $this->status) {
                throw ValidationException::withMessages(['status' => trans('general.infos.nothing_to_submit')]);
            }

            if ($this->status != TransferRequestStatus::APPROVED->value) {
                return;
            }

            $certificate = Certificate::query()
                ->byTeam()
                ->where('code_number', $this->transfer_certificate_number)
                ->whereHas('template', function ($q) {
                    $q->where('type', CertificateType::TRANSFER_CERTIFICATE->value);
                })
                ->where('model_type', 'Student')
                ->where('model_id', $transferRequest->student_id)
                ->exists();

            if (! $certificate) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.certificate.certificate')])]);
            }

            $reason = Option::query()
                ->byTeam()
                ->where('type', OptionType::STUDENT_TRANSFER_REASON->value)
                ->whereUuid($this->reason)
                ->getOrFail(trans('student.transfer_reason.transfer_reason'), 'reason');

            $existingTransferCertificateNumber = Student::query()
                ->byTeam()
                ->where('meta->transfer_certificate_number', $this->transfer_certificate_number)
                ->exists();

            if ($existingTransferCertificateNumber) {
                throw ValidationException::withMessages(['transfer_certificate_number' => trans('validation.unique', ['attribute' => trans('academic.certificate.props.code_number')])]);
            }

            $student = Student::query()
                ->byPeriod()
                ->summary()
                ->filterAccessible()
                ->where('students.id', $transferRequest->student_id)
                ->getOrFail(trans('student.student'));

            if ($student->joining_date > $this->transfer_date) {
                throw ValidationException::withMessages(['date' => trans('student.transfer.date_before_joining', ['attribute' => \Cal::date($student->joining_date)->formatted])]);
            }

            if ($student->start_date->value > $this->transfer_date) {
                throw ValidationException::withMessages(['date' => trans('student.transfer.date_before_promotion', ['attribute' => $student->start_date->formatted])]);
            }

            if ($student->leaving_date) {
                throw ValidationException::withMessages(['student' => trans('student.transfer.already_transferred')]);
            }

            // $fee = $student->getFeeSummary();

            // $total = Arr::get($fee, 'total_fee');
            // $paid = Arr::get($fee, 'paid_fee');
            // $balance = Arr::get($fee, 'balance_fee');

            // if ($balance->value > 0) {
            //     throw ValidationException::withMessages(['message' => trans('student.transfer_request.fee_due', ['total' => $total->formatted, 'paid' => $paid->formatted])]);
            // }

            $promotedStudent = Student::query()
                ->whereAdmissionId($student->admission_id)
                ->where('id', '!=', $student->id)
                ->where('start_date', '>', $student->start_date->value)
                ->exists();

            if ($promotedStudent) {
                throw ValidationException::withMessages(['student' => trans('student.transfer.already_promoted')]);
            }

            $this->merge([
                'transfer_request' => $transferRequest,
                'student' => $student,
                'reason_id' => $reason->id,
                'date' => $this->transfer_date,
                'remarks' => $this->comment,
                'transfer_request' => true,
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
            'status' => trans('student.transfer_request.props.status'),
            'comment' => trans('student.transfer_request.props.comment'),
            'transfer_certificate_number' => trans('academic.certificate.props.code_number'),
            'transfer_date' => trans('student.transfer.props.date'),
            'reason' => trans('student.transfer_reason.transfer_reason'),
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
