<?php

namespace App\Http\Requests\Post;

use App\Enums\Post\Visibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PostRequest extends FormRequest
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
            'content' => ['required', 'min:1', 'max:10000'],
            'images' => ['nullable', 'array'],
            'visibility' => ['required', new Enum(Visibility::class)],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $postUuid = $this->route('post');

            $this->merge([
                //
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
            'content' => __('post.props.content'),
            'images' => __('post.props.images'),
            'visibility' => __('post.props.visibility'),
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
