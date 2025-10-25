<?php

namespace App\Http\Requests;

use App\Enums\GalleryType;
use App\Models\Gallery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GalleryRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'type' => ['required', new Enum(GalleryType::class)],
            'date' => 'required|date_format:Y-m-d',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('gallery');

            $existingTitles = Gallery::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereTitle($this->title)
                ->exists();

            if ($existingTitles) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('gallery.props.title')]));
            }

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
            'title' => __('gallery.props.title'),
            'type' => __('gallery.props.type'),
            'date' => __('gallery.props.date'),
            'description' => __('gallery.props.description'),
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
