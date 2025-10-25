<?php

namespace App\Http\Requests\Reception;

use App\Enums\Reception\EnquiryStatus;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class EnquiryFollowUpRequest extends FormRequest
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
            'follow_up_date' => 'required|date_format:Y-m-d',
            'next_follow_up_date' => 'nullable|date_format:Y-m-d|after_or_equal:follow_up_date',
            'status' => ['required', new Enum(EnquiryStatus::class)],
            'stage' => 'nullable|uuid',
            'remarks' => 'nullable|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $stage = $this->stage ? Option::query()
                ->byTeam()
                ->whereUuid($this->stage)
                ->getOrFail(trans('reception.enquiry.stage.stage')) : null;

            $this->merge([
                'stage_id' => $stage?->id,
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
            'follow_up_date' => __('reception.enquiry.follow_up.props.follow_up_date'),
            'next_follow_up_date' => __('reception.enquiry.follow_up.props.next_follow_up_date'),
            'status' => __('reception.enquiry.follow_up.props.status'),
            'stage' => __('reception.enquiry.stage.stage'),
            'remarks' => __('reception.enquiry.follow_up.props.remarks'),
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
