<?php

namespace App\Http\Requests\Site;

use App\Models\Site\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockRequest extends FormRequest
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
        $uuid = $this->route('block.uuid');

        $rules = [
            'name' => ['required', 'alpha_dash', 'max:50', Rule::unique('site_blocks')->ignore($uuid, 'uuid')],
            'is_slider' => 'sometimes|boolean',
        ];

        if (! $this->is_slider) {
            $rules['title'] = 'required|max:255';
            $rules['sub_title'] = 'nullable|max:255';
            $rules['content'] = 'nullable|max:1000';
            $rules['menu'] = 'nullable|uuid';
            $rules['url'] = 'nullable|max:255|url';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $blockUuid = $this->route('block.uuid');

            if (in_array($this->name, ['EVENT_LIST', 'BLOG_LIST', 'CONTACT'])) {
                $validator->errors()->add('name', trans('validation.reserved', ['attribute' => trans('site.block.props.name')]));
            }

            if ($this->is_slider) {
                $this->merge([
                    'title' => null,
                    'sub_title' => null,
                    'content' => null,
                ]);
            } else {
                $menu = $this->menu ? Menu::query()
                    ->where('uuid', $this->menu)
                    ->getOrFail(trans('site.menu.menu')) : null;

                if ($menu) {
                    $this->merge([
                        'menu_id' => $menu?->id,
                    ]);
                }
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
            'name' => __('site.block.props.name'),
            'title' => __('site.block.props.title'),
            'sub_title' => __('site.block.props.sub_title'),
            'content' => __('site.block.props.content'),
            'url' => __('site.block.props.url'),
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
