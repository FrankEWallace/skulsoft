<?php

namespace App\Http\Requests\Reception;

use App\Models\Contact;
use App\Models\Media;
use App\Models\Option;
use App\Models\Reception\Complaint;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ComplaintRequest extends FormRequest
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
            'code_number' => ['nullable', 'string', 'max:20'],
            'type' => 'required|uuid',
            'subject' => 'required|min:2|max:255',
            // 'time' => 'required|date_format:H:i:s',
            'description' => 'required',
        ];

        if (auth()->user()->is_student_or_guardian) {
            $rules['student'] = 'required|uuid';
            $rules['date'] = 'nullable';
        } else {
            $rules['complainant_name'] = 'required|min:2|max:100';
            $rules['complainant_contact_number'] = 'required|max:20';
            $rules['complainant_address'] = 'required|min:2|max:100';
            $rules['date'] = 'required|date_format:Y-m-d';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Complaint)->getModelName();

            $complaintUuid = $this->route('complaint');

            $type = $this->type ? Option::query()
                ->byTeam()
                ->whereUuid($this->type)
                ->getOrFail(__('reception.complaint.type.type'), 'type') : null;

            if (auth()->user()->is_student_or_guardian) {
                $contact = Contact::query()
                    ->whereUserId(auth()->id())
                    ->first();

                $this->merge([
                    'is_online' => true,
                    'date' => today()->toDateString(),
                    'complainant_name' => $contact?->name,
                    'complainant_contact_number' => $contact?->contact_number,
                    'complainant_address' => Arr::toAddress($contact?->present_address),
                ]);
            }

            $student = $this->student ? Student::query()
                ->byPeriod()
                ->summary()
                ->filterAccessible()
                ->where('students.uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student') : null;

            $existingComplaint = Complaint::query()
                ->when($complaintUuid, function ($q, $complaintUuid) {
                    $q->where('uuid', '!=', $complaintUuid);
                })
                ->whereTypeId($type?->id)
                ->where('date', $this->date)
                ->where('model_type', 'Student')
                ->where('model_id', $student?->id)
                ->exists();

            if ($existingComplaint) {
                $validator->errors()->add('type', trans('global.duplicate', ['attribute' => __('reception.complaint.complaint')]));
            }

            if (auth()->user()->is_student_or_guardian) {
                $attachedMedia = Media::whereModelType($mediaModel)
                    ->whereToken($this->media_token)
                    // ->where('meta->hash', $this->media_hash)
                    ->where('meta->is_temp_deleted', false)
                    ->where(function ($q) use ($complaintUuid) {
                        $q->whereStatus(0)
                            ->when($complaintUuid, function ($q) {
                                $q->orWhere('status', 1);
                            });
                    })
                    ->exists();

                if (! $attachedMedia) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
                }
            }

            $this->merge([
                'student_id' => $student?->id,
                'type_id' => $type?->id,
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
            'code_number' => __('reception.complaint.props.code_number'),
            'student' => __('student.student'),
            'type' => __('reception.complaint.props.type'),
            'subject' => __('reception.complaint.props.subject'),
            'complainant_name' => __('reception.complaint.props.name'),
            'complainant_contact_number' => __('reception.complaint.props.contact_number'),
            'complainant_address' => __('reception.complaint.props.address'),
            'date' => __('reception.complaint.props.date'),
            'description' => __('reception.complaint.props.description'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
