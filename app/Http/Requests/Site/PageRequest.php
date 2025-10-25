<?php

namespace App\Http\Requests\Site;

use App\Models\Site\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
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
        $uuid = $this->route('page.uuid');

        return [
            'name' => ['required', 'max:50', Rule::unique('site_pages')->ignore($uuid, 'uuid')],
            'title' => 'required|string|min:3|max:255',
            'sub_title' => 'nullable|string|min:3|max:255',
            'content' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Page)->getModelName();

            $pageUuid = $this->route('page.uuid');
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
            'name' => __('site.page.props.name'),
            'title' => __('site.page.props.title'),
            'sub_title' => __('site.page.props.sub_title'),
            'content' => __('site.page.props.content'),
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
