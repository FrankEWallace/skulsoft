<?php

namespace App\Http\Requests;

use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccountRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:50', Rule::unique('users')->ignore(\Auth::id())],
            'username' => ['required', Rule::unique('users')->ignore(\Auth::id()), new Username],
        ];
    }
}
