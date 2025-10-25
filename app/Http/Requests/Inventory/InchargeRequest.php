<?php

namespace App\Http\Requests\Inventory;

use App\Concerns\HasIncharge;
use App\Models\Employee\Employee;
use App\Models\Inventory\Inventory;
use Illuminate\Foundation\Http\FormRequest;

class InchargeRequest extends FormRequest
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
            'inventory' => 'required|uuid',
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
            $uuid = $this->route('incharge');

            $inventory = Inventory::query()
                ->byTeam()
                ->filterAccessible()
                ->where('uuid', $this->inventory)
                ->getOrFail(trans('inventory.inventory'), 'inventory');

            $employee = Employee::query()
                ->byTeam()
                ->where('uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            $this->validateInput(employee: $employee, model: $inventory, uuid: $uuid);

            $this->merge([
                'inventory_id' => $inventory->id,
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
            'inventory' => __('inventory.inventory'),
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
