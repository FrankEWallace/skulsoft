<?php

namespace App\Http\Requests\Reception;

use App\Models\Employee\Employee;
use App\Models\Reception\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class ComplaintAssignRequest extends FormRequest
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
            'employee' => 'nullable|uuid',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Complaint)->getModelName();

            $complaintUuid = $this->route('complaint');

            $employee = $this->employee ? Employee::query()
                ->byTeam()
                ->whereUuid($this->employee)
                ->getOrFail(__('employee.employee'), 'employee') : null;

            $this->merge([
                'employee_id' => $employee?->id,
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
            'employee' => __('employee.employee'),
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
