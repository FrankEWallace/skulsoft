<?php

namespace App\Http\Requests\Student;

use App\Enums\FamilyRelation;
use App\Models\Guardian;
use App\Rules\AlphaSpace;
use App\Rules\StrongPassword;
use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class GuardianRequest extends FormRequest
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
            'type' => ['required', 'in:new,existing'],
            'name' => ['required_if:type,new', 'min:2', 'max:200', new AlphaSpace],
            'relation' => ['required', new Enum(FamilyRelation::class)],
            'contact_number' => ['required_if:type,new', 'min:2', 'max:20'],
        ];

        if ($this->create_user_account && $this->method() == 'POST') {
            $rules['email'] = 'nullable|email';
            $rules['username'] = ['nullable', new Username, Rule::unique('users')];
            $rules['password'] = ['required', 'same:password_confirmation', new StrongPassword];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $studentUuid = $this->route('student');

            if ($this->type == 'existing') {
                $existingGuardian = Guardian::query()
                    ->with('contact', 'primary')
                    ->whereHas('contact', function ($q) {
                        $q->whereTeamId(auth()->user()?->current_team_id);
                    })
                    ->whereUuid($this->guardian)
                    ->first();

                if (! $existingGuardian) {
                    $validator->errors()->add('guardian', trans('global.could_not_find', ['attribute' => trans('guardian.guardian')]));
                }

                $this->merge([
                    'name' => $existingGuardian->contact->name,
                    'contact_number' => $existingGuardian->contact->contact_number,
                    'guardian_contact_id' => $existingGuardian->contact_id,
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
            'name' => __('contact.props.name'),
            'relation' => __('contact.props.relation'),
            'contact_number' => __('contact.props.contact_number'),
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
