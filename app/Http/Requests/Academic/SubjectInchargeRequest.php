<?php

namespace App\Http\Requests\Academic;

use App\Concerns\HasIncharge;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Employee\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SubjectInchargeRequest extends FormRequest
{
    use HasIncharge;

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
            'batch' => 'nullable|uuid',
            'batches' => 'nullable|array',
            'subject' => 'required|uuid',
            'employee' => 'required|uuid',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'remarks' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('subject_incharge');

            $subject = null;
            if ($this->method() == 'POST') {
                $batches = $this->batches ? Batch::query()
                    ->byPeriod()
                    ->filterAccessible()
                    ->whereIn('uuid', $this->batches)
                    ->get() : [];

                if ($this->batches && $batches->isEmpty()) {
                    $validator->errors()->add('subject', trans('global.could_not_find', ['attribute' => trans('academic.batch.batch')]));
                }

                foreach ($batches as $batch) {
                    $subject = Subject::query()
                        ->withSubjectRecord($batch->id, $batch->course_id)
                        ->where('subjects.uuid', $this->subject)
                        ->first();

                    if (! $subject) {
                        throw ValidationException::withMessages(['subject' => trans('academic.subject.subject_not_associated_with_batch')]);
                    }
                }
            } else {
                $batch = $this->batch ? Batch::query()
                    ->byPeriod()
                    ->filterAccessible()
                    ->where('uuid', $this->batch)
                    ->getOrFail(trans('academic.batch.batch'), 'batch') : null;

                $subject = $this->batch ? Subject::query()
                    ->findByBatchOrFail($batch->id, $batch->course_id, $this->subject) : Subject::query()
                    ->byPeriod()
                    ->where('uuid', $this->subject)
                    ->getOrFail(trans('academic.subject.subject'), 'subject');
            }

            $employee = Employee::query()
                ->byTeam()
                ->where('uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            if ($this->method() == 'POST' && $subject) {
                foreach ($batches as $batch) {
                    $this->validateInput(employee: $employee, model: $subject, detail: $batch, uuid: $uuid);
                }
            } else {
                $this->validateInput(employee: $employee, model: $subject, detail: $batch, uuid: $uuid);
            }

            $this->merge([
                'batch_id' => $batch?->id,
                'batches' => $batches,
                'subject_id' => $subject?->id,
                'employee_id' => $employee->id,
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
            'subject' => __('academic.subject.subject'),
            'employee' => __('employee.employee'),
            'start_date' => __('employee.incharge.props.start_date'),
            'end_date' => __('employee.incharge.props.end_date'),
            'remarks' => __('employee.incharge.props.remarks'),
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
            //
        ];
    }
}
