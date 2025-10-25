<?php

namespace App\Http\Requests\Site;

use App\Enums\Site\MenuPlacement;
use App\Models\Site\Menu;
use App\Models\Site\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class MenuRequest extends FormRequest
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
        $uuid = $this->route('menu.uuid');

        return [
            'name' => ['required', 'max:50', Rule::unique('site_menus')->ignore($uuid, 'uuid')],
            'placement' => ['required', new Enum(MenuPlacement::class)],
            'parent' => 'nullable|uuid',
            'page' => 'nullable|uuid',
            'has_external_url' => 'boolean',
            'external_url' => 'required_if:has_external_url,true|url',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $menuUuid = $this->route('menu.uuid');

            $page = $this->page ? Page::query()
                ->where('uuid', $this->page)
                ->getOrFail(trans('site.page.page')) : null;

            $parent = $this->parent ? Menu::query()
                ->where('uuid', $this->parent)
                ->getOrFail(trans('site.menu.menu')) : null;

            if ($parent && ! empty($parent->parent_id)) {
                $validator->errors()->add('parent', __('site.menu.could_not_have_nested_menu'));
            }

            $this->merge([
                'parent_id' => $parent?->id,
                'page_id' => $page?->id,
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
            'name' => __('site.menu.props.name'),
            'placement' => __('site.menu.props.placement'),
            'parent' => __('site.menu.props.parent'),
            'page' => __('site.page.page'),
            'has_external_url' => __('site.menu.props.external_url'),
            'external_url' => __('site.menu.props.external_url'),
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
            'external_url.required_if' => __('validation.required', ['attribute' => __('site.menu.props.external_url')]),
        ];
    }
}
