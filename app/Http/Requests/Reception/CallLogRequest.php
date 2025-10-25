<?php

namespace App\Http\Requests\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\CallType;
use App\Models\Option;
use App\Models\Reception\CallLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CallLogRequest extends FormRequest
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
            'type' => ['required', new Enum(CallType::class)],
            'purpose' => 'required|uuid',
            'name' => 'required|min:2|max:100',
            'company_name' => 'nullable|min:2|max:255',
            'incoming_number' => 'required|string|max:20',
            'outgoing_number' => 'required|string|max:20',
            'call_at' => 'required|date_format:Y-m-d H:i:s',
            'duration' => 'required|integer|min:1|max:1000',
            'conversation' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new CallLog)->getModelName();

            $callLogUuid = $this->route('call_log.uuid');

            $purpose = Option::query()
                ->byTeam()
                ->whereType(OptionType::CALLING_PURPOSE->value)
                ->whereUuid($this->purpose)
                ->getOrFail(__('reception.call_log.purpose.purpose'), 'purpose');

            $this->merge([
                'purpose_id' => $purpose?->id,
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
            'type' => __('reception.call_log.props.type'),
            'purpose' => __('reception.call_log.props.purpose'),
            'name' => __('reception.call_log.props.name'),
            'company_name' => __('reception.call_log.props.company_name'),
            'call_at' => __('reception.call_log.props.call_at'),
            'duration' => __('reception.call_log.props.duration'),
            'incoming_number' => __('reception.call_log.props.incoming_number'),
            'outgoing_number' => __('reception.call_log.props.outgoing_number'),
            'conversation' => __('reception.call_log.props.conversation'),
            'remarks' => __('reception.call_log.props.remarks'),
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
