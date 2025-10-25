<?php

namespace App\Http\Requests\Approval;

use App\Enums\Approval\Category;
use App\Models\Approval\Type;
use App\Models\Employee\Department;
use App\Models\Employee\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class TypeRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'category' => ['required', new Enum(Category::class)],
            'department' => ['nullable', 'uuid'],
            'levels' => ['required', 'array'],
            'levels.*.employee' => ['required', 'uuid', 'distinct'],
            'levels.*.actions' => ['required', 'array'],
            'is_active' => 'boolean',
            'enable_file_upload' => 'boolean',
            'is_file_upload_required' => 'boolean',
            'description' => 'nullable|min:2|max:1000',
        ];

        if ($this->category == Category::ITEM_BASED->value) {
            $rules['item_based_type'] = ['required', 'in:item_with_quantity,item_without_quantity'];
        }

        if ($this->category == Category::CONTACT_BASED->value) {
            $rules['enable_contact_number'] = 'boolean';
            $rules['is_contact_number_required'] = 'boolean';
            $rules['enable_email'] = 'boolean';
            $rules['is_email_required'] = 'boolean';
            $rules['enable_website'] = 'boolean';
            $rules['is_website_required'] = 'boolean';
            $rules['enable_tax_number'] = 'boolean';
            $rules['is_tax_number_required'] = 'boolean';
            $rules['enable_address'] = 'boolean';
            $rules['is_address_required'] = 'boolean';
        }

        if ($this->category == Category::PAYMENT_BASED->value) {
            $rules['enable_invoice_number'] = 'boolean';
            $rules['is_invoice_number_required'] = 'boolean';
            $rules['enable_invoice_date'] = 'boolean';
            $rules['is_invoice_date_required'] = 'boolean';
            $rules['enable_payment_mode'] = 'boolean';
            $rules['is_payment_mode_required'] = 'boolean';
            $rules['enable_payment_details'] = 'boolean';
            $rules['is_payment_details_required'] = 'boolean';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('type');

            $department = $this->department ? Department::query()
                ->globalOrByTeam()
                ->where('uuid', $this->department)
                ->getOrFail(trans('employee.department.department'), 'department') : null;

            $existingNames = Type::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->whereCategory($this->category)
                ->whereDepartmentId($department?->id)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('approval.type.props.name')]));
            }

            $employees = Employee::query()
                ->with('contact')
                ->whereIn('uuid', collect($this->levels)->pluck('employee'))
                ->get();

            $newLevels = [];
            foreach ($this->levels as $level) {
                $employee = $employees->firstWhere('uuid', Arr::get($level, 'employee'));

                if (! $employee) {
                    $validator->errors()->add('levels.*.employee', trans('validation.exists', ['attribute' => __('employee.employee')]));
                }

                $otherTeamMember = Arr::get($level, 'other_team_member');

                if (!$otherTeamMember && $employee->team_id != auth()->user()->current_team_id) {
                    $validator->errors()->add('levels.*.employee', trans('validation.exists', ['attribute' => __('employee.employee')]));
                } else if ($otherTeamMember && $employee->team_id == auth()->user()->current_team_id) {
                    $validator->errors()->add('levels.*.employee', trans('validation.exists', ['attribute' => __('employee.employee')]));
                }

                if (! $employee->contact->user_id) {
                    $validator->errors()->add('levels.*.employee', trans('global.could_not_find', ['attribute' => __('user.user')]));
                }

                $level = Arr::add($level, 'employee_id', $employee?->id);
                $level = Arr::add($level, 'user_id', $employee?->contact?->user_id);
                $newLevels[] = $level;
            }

            $this->merge([
                'levels' => $newLevels,
                'department_id' => $department?->id,
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
            'name' => __('approval.type.props.name'),
            'category' => __('approval.type.props.category'),
            'department' => __('employee.department.department'),
            'levels' => __('approval.type.props.levels'),
            'levels.*.employee' => __('employee.employee'),
            'levels.*.actions' => __('approval.type.props.action'),
            'is_active' => __('approval.type.statuses.active'),
            'enable_file_upload' => __('approval.type.props.file_upload'),
            'is_file_upload_required' => __('approval.type.props.file_upload'),
            'description' => __('approval.type.props.description'),
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
