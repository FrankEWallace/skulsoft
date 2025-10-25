<?php

namespace App\Http\Requests\Student;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Enums\ServiceType;
use App\Models\Media;
use App\Models\Student\ServiceAllocation;
use App\Models\Student\ServiceRequest;
use App\Models\Student\Student;
use App\Models\Transport\Stoppage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class ServiceRequestRequest extends FormRequest
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
            'date' => 'required|date_format:Y-m-d',
            'type' => ['required', new Enum(ServiceType::class)],
            'request_type' => ['required', new Enum(ServiceRequestType::class)],
            'transport_stoppage' => 'nullable|uuid',
            'description' => 'required|max:500',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('service_request');

            $mediaModel = (new ServiceRequest)->getModelName();

            if (! in_array($this->type, explode(',', config('config.student.services')))) {
                throw ValidationException::withMessages(['type' => trans('general.errors.invalid_input')]);
            }

            $student = Student::query()
                ->byPeriod()
                ->summary()
                ->filterAccessible()
                ->where('students.uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student');

            if ($student->joining_date > $this->date) {
                throw ValidationException::withMessages(['date' => trans('student.service_request.date_before_joining', ['attribute' => \Cal::date($student->joining_date)->formatted])]);
            }

            if ($student->start_date->value > $this->date) {
                throw ValidationException::withMessages(['date' => trans('student.service_request.date_before_promotion', ['attribute' => $student->start_date->formatted])]);
            }

            if ($student->user_id == auth()->id() && $student->leaving_date && $student->leaving_date < today()->toDateString()) {
                throw ValidationException::withMessages(['student' => trans('student.transfer_request.already_transferred')]);
            }

            $transportStoppage = null;
            if ($this->type == ServiceType::TRANSPORT->value && $this->request_type == ServiceRequestType::OPT_IN->value) {
                if (! $this->transport_stoppage) {
                    throw ValidationException::withMessages(['transport_stoppage' => trans('validation.required', ['attribute' => trans('transport.stoppage.stoppage')])]);
                } else {
                    $transportStoppage = Stoppage::query()
                        ->byPeriod()
                        ->where('uuid', $this->transport_stoppage)
                        ->getOrFail(trans('transport.stoppage.stoppage'), 'transport_stoppage');
                }
            }

            $existingRequest = ServiceRequest::query()
                ->when($uuid, function ($q) use ($uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereModelType('Student')
                ->whereModelId($student->id)
                ->where('date', $this->date)
                ->where('type', $this->type)
                ->where('request_type', $this->request_type)
                ->where('status', '=', ServiceRequestStatus::REQUESTED)
                ->exists();

            if ($existingRequest) {
                throw ValidationException::withMessages(['date' => trans('student.service_request.already_requested', ['attribute' => \Cal::date($this->date)->formatted])]);
            }

            if (auth()->user()->is_student_or_guardian) {
                if ($this->date < today()->toDateString()) {
                    throw ValidationException::withMessages(['date' => trans('validation.after', ['attribute' => trans('student.service_request.props.date'), 'date' => \Cal::date(today())->formatted])]);
                }

                $attachedMedia = Media::whereModelType($mediaModel)
                    ->whereToken($this->media_token)
                    // ->where('meta->hash', $this->media_hash)
                    ->where('meta->is_temp_deleted', false)
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

            if ($this->request_type == 'opt_in') {
                $existingServiceAllocation = ServiceAllocation::query()
                    ->where('model_id', $student->id)
                    ->where('model_type', 'Student')
                    ->where('type', $this->type)
                    ->first();

                if (in_array($this->type, [ServiceType::MESS->value, ServiceType::HOSTEL->value]) && $existingServiceAllocation) {
                    throw ValidationException::withMessages([
                        'message' => trans('student.service_request.already_opted_in'),
                    ]);
                }

                if ($this->type == ServiceType::TRANSPORT->value && $existingServiceAllocation && $transportStoppage?->id == $existingServiceAllocation->transport_stoppage_id) {
                    throw ValidationException::withMessages([
                        'message' => trans('student.service_request.already_opted_in'),
                    ]);
                }
            } elseif ($this->request_type == 'opt_out') {
                $existingServiceAllocation = ServiceAllocation::query()
                    ->where('model_id', $student->id)
                    ->where('model_type', 'Student')
                    ->where('type', $this->type)
                    ->first();

                if (! $existingServiceAllocation) {
                    throw ValidationException::withMessages([
                        'message' => trans('student.service_request.already_opted_out'),
                    ]);
                }
            }

            $this->merge([
                'student' => $student,
                'student_id' => $student->id,
                'transport_stoppage_id' => $transportStoppage?->id,
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
            'date' => trans('student.service_request.props.date'),
            'type' => trans('student.service_request.props.type'),
            'request_type' => trans('student.service_request.props.request_type'),
            'description' => trans('student.service_request.props.description'),
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
