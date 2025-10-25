<?php

namespace App\Http\Requests\Activity;

use App\Models\Activity\TripParticipant;
use App\Models\Employee\Employee;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;

class TripParticipantRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:student,employee'],
            'participant' => ['required', 'uuid'],
            'fee' => ['required', 'numeric', 'min:0'],
            'payments' => ['array'],
            'payments.*.code_number' => ['required', 'string', 'max:50', 'distinct'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.date' => ['required', 'date_format:Y-m-d'],
            'payments.*.payment_method' => ['required', 'string', 'max:50'],
            'payments.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $tripUuid = $this->route('trip');
            $tripParticipantUuid = $this->route('participant');

            $participant = null;
            if ($this->type == 'student') {
                $participant = Student::query()
                    ->byTeam()
                    ->whereUuid($this->participant)
                    ->getOrFail(__('student.student'), 'participant');
            } elseif ($this->type == 'employee') {
                $participant = Employee::query()
                    ->byTeam()
                    ->whereUuid($this->participant)
                    ->getOrFail(__('employee.employee'), 'participant');
            }

            $existingParticipant = TripParticipant::whereHas('trip', function ($q) use ($tripUuid) {
                $q->whereUuid($tripUuid);
            })
                ->when($tripParticipantUuid, function ($q, $tripParticipantUuid) {
                    $q->where('uuid', '!=', $tripParticipantUuid);
                })
                ->when($this->type == 'student', function ($q) use ($participant) {
                    $q->where('model_type', 'Student')
                        ->where('model_id', $participant->id);
                })
                ->when($this->type == 'employee', function ($q) use ($participant) {
                    $q->where('model_type', 'Employee')
                        ->where('model_id', $participant->id);
                })
                ->exists();

            if ($existingParticipant) {
                $validator->errors()->add('participant', trans('validation.unique', ['attribute' => __('activity.trip.participant.participant')]));
            }

            if ($this->fee > 0) {
                $totalPayment = collect($this->payments)->sum('amount');
                if ($totalPayment > $this->fee) {
                    $validator->errors()->add('payments.*.amount', trans('validation.max.numeric', ['attribute' => __('finance.transaction.props.amount'), 'max' => \Price::from($this->fee)?->formatted]));
                }

                $this->merge([
                    'paid' => $totalPayment,
                ]);
            }

            $this->merge([
                'student_id' => $this->type == 'student' ? $participant->id : null,
                'employee_id' => $this->type == 'employee' ? $participant->id : null,
                'participant_id' => $participant->id,
            ]);

            if ($this->fee <= 0) {
                $this->merge([
                    'payments' => [],
                ]);
            }
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
            'type' => __('activity.trip.participant.props.type'),
            'participant' => __('activity.trip.participant.participant'),
            'fee' => __('activity.trip.props.fee'),
            'payments.*.code_number' => __('finance.transaction.props.code_number'),
            'payments.*.amount' => __('finance.transaction.props.amount'),
            'payments.*.date' => __('finance.transaction.props.date'),
            'payments.*.payment_method' => __('finance.payment_method.payment_method'),
            'payments.*.description' => __('finance.transaction.props.description'),
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
