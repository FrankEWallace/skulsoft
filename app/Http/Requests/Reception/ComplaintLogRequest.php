<?php

namespace App\Http\Requests\Reception;

use App\Enums\Reception\ComplaintStatus;
use App\Models\Reception\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ComplaintLogRequest extends FormRequest
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
            'status' => ['required', new Enum(ComplaintStatus::class)],
            'action' => ['required_without:comment', 'max:1000'],
            'comment' => ['required_without:action', 'max:1000'],
            'remarks' => ['nullable', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Complaint)->getModelName();

            $complaintUuid = $this->route('complaint');

            $this->merge([]);
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
            'status' => __('reception.complaint.props.status'),
            'action' => __('reception.complaint.props.action'),
            'comment' => __('reception.complaint.props.comment'),
            'remarks' => __('reception.complaint.props.remarks'),
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
            'action.required_without' => trans('validation.required', ['attribute' => trans('reception.complaint.props.action')]),
            'comment.required_without' => trans('validation.required', ['attribute' => trans('reception.complaint.props.comment')]),
        ];
    }
}
