<?php

namespace App\Http\Requests\Team;

use App\Concerns\SimpleValidation;
use Illuminate\Foundation\Http\FormRequest;

class ConfigRequest extends FormRequest
{
    use SimpleValidation;

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
            'name' => 'sometimes|required|min:2|max:100',
            'title1' => 'sometimes|nullable|min:2|max:100',
            'title2' => 'sometimes|nullable|min:2|max:100',
            'title3' => 'sometimes|nullable|min:2|max:100',
            'address_line1' => 'sometimes|required|min:2|max:100',
            'address_line2' => 'sometimes|nullable|min:2|max:100',
            'city' => 'sometimes|required|min:2|max:100',
            'state' => 'sometimes|required|min:2|max:100',
            'country' => 'sometimes|required|min:2|max:100',
            'zipcode' => 'sometimes|required|min:2|max:100',
            'phone' => 'sometimes|required|min:2|max:100',
            'email' => 'sometimes|required|email|min:2|max:100',
            'website' => 'sometimes|nullable|url|min:2|max:100',
            'fax' => 'sometimes|nullable|min:2|max:100',
            'incharge1.title' => 'sometimes|nullable|string|max:100',
            'incharge1.name' => 'sometimes|nullable|string|max:100',
            'incharge1.email' => 'sometimes|nullable|email|max:100',
            'incharge1.contact_number' => 'sometimes|nullable|string|max:20',
            'incharge2.title' => 'sometimes|nullable|string|max:100',
            'incharge2.name' => 'sometimes|nullable|string|max:100',
            'incharge2.email' => 'sometimes|nullable|email|max:100',
            'incharge2.contact_number' => 'sometimes|nullable|string|max:20',
            'incharge3.title' => 'sometimes|nullable|string|max:100',
            'incharge3.name' => 'sometimes|nullable|string|max:100',
            'incharge3.email' => 'sometimes|nullable|email|max:100',
            'incharge3.contact_number' => 'sometimes|nullable|string|max:20',
            'incharge4.title' => 'sometimes|nullable|string|max:100',
            'incharge4.name' => 'sometimes|nullable|string|max:100',
            'incharge4.email' => 'sometimes|nullable|email|max:100',
            'incharge4.contact_number' => 'sometimes|nullable|string|max:20',
            'incharge5.title' => 'sometimes|nullable|string|max:100',
            'incharge5.name' => 'sometimes|nullable|string|max:100',
            'incharge5.email' => 'sometimes|nullable|email|max:100',
            'incharge5.contact_number' => 'sometimes|nullable|string|max:20',
        ];
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => __('team.config.general.props.name'),
            'title1' => __('team.config.general.props.title1'),
            'title2' => __('team.config.general.props.title2'),
            'title3' => __('team.config.general.props.title3'),
            'address_line1' => __('team.config.general.props.address_line1'),
            'address_line2' => __('team.config.general.props.address_line2'),
            'city' => __('team.config.general.props.city'),
            'state' => __('team.config.general.props.state'),
            'country' => __('team.config.general.props.country'),
            'zipcode' => __('team.config.general.props.zipcode'),
            'phone' => __('team.config.general.props.phone'),
            'email' => __('team.config.general.props.email'),
            'website' => __('team.config.general.props.website'),
            'fax' => __('team.config.general.props.fax'),
            'incharge1.title' => __('team.config.general.props.incharge_title'),
            'incharge1.name' => __('team.config.general.props.incharge_name'),
            'incharge1.email' => __('team.config.general.props.incharge_email'),
            'incharge1.contact_number' => __('team.config.general.props.incharge_contact_number'),
            'incharge2.title' => __('team.config.general.props.incharge_title'),
            'incharge2.name' => __('team.config.general.props.incharge_name'),
            'incharge2.email' => __('team.config.general.props.incharge_email'),
            'incharge2.contact_number' => __('team.config.general.props.incharge_contact_number'),
            'incharge3.title' => __('team.config.general.props.incharge_title'),
            'incharge3.name' => __('team.config.general.props.incharge_name'),
            'incharge3.email' => __('team.config.general.props.incharge_email'),
            'incharge3.contact_number' => __('team.config.general.props.incharge_contact_number'),
            'incharge4.title' => __('team.config.general.props.incharge_title'),
            'incharge4.name' => __('team.config.general.props.incharge_name'),
            'incharge4.email' => __('team.config.general.props.incharge_email'),
            'incharge4.contact_number' => __('team.config.general.props.incharge_contact_number'),
            'incharge5.title' => __('team.config.general.props.incharge_title'),
            'incharge5.name' => __('team.config.general.props.incharge_name'),
            'incharge5.email' => __('team.config.general.props.incharge_email'),
            'incharge5.contact_number' => __('team.config.general.props.incharge_contact_number'),
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

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            $validator->after(function ($validator) {
                $this->change($validator, 'incharge1.title', 'incharge1Ttitle');
                $this->change($validator, 'incharge1.name', 'incharge1Name');
                $this->change($validator, 'incharge1.email', 'incharge1Email');
                $this->change($validator, 'incharge1.contact_number', 'incharge1ContactNumber');
                $this->change($validator, 'incharge2.title', 'incharge2Ttitle');
                $this->change($validator, 'incharge2.name', 'incharge2Name');
                $this->change($validator, 'incharge2.email', 'incharge2Email');
                $this->change($validator, 'incharge2.contact_number', 'incharge2ContactNumber');
                $this->change($validator, 'incharge3.title', 'incharge3Ttitle');
                $this->change($validator, 'incharge3.name', 'incharge3Name');
                $this->change($validator, 'incharge3.email', 'incharge3Email');
                $this->change($validator, 'incharge3.contact_number', 'incharge3ContactNumber');
                $this->change($validator, 'incharge4.title', 'incharge4Ttitle');
                $this->change($validator, 'incharge4.name', 'incharge4Name');
                $this->change($validator, 'incharge4.email', 'incharge4Email');
                $this->change($validator, 'incharge4.contact_number', 'incharge4ContactNumber');
                $this->change($validator, 'incharge5.title', 'incharge5Ttitle');
                $this->change($validator, 'incharge5.name', 'incharge5Name');
                $this->change($validator, 'incharge5.email', 'incharge5Email');
                $this->change($validator, 'incharge5.contact_number', 'incharge5ContactNumber');
            });

            return;
        }

        $validator->after(function ($validator) {
            $team = $this->route('team');
        });
    }
}
