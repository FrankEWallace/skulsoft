<?php

namespace App\Http\Requests\Hostel;

use App\Models\Hostel\Block;
use App\Models\Hostel\Floor;
use Illuminate\Foundation\Http\FormRequest;

class FloorRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'alias' => 'nullable|min:2|max:100',
            'block' => 'required|uuid',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('floor');

            $block = Block::query()
                ->byTeam()
                ->hostel()
                ->whereUuid($this->block)
                ->getOrFail(__('hostel.block.block'), 'block');

            $this->merge([
                'block_id' => $block->id,
            ]);

            $existingNames = Floor::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereBlockId($block->id)
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('hostel.floor.floor')]));
            }

            if (! $this->alias) {
                return;
            }

            $existingAliases = Floor::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereBlockId($block->id)
                ->whereAlias($this->alias)
                ->exists();

            if ($existingAliases) {
                $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('hostel.floor.floor')]));
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
            'name' => __('hostel.floor.props.name'),
            'alias' => __('hostel.floor.props.alias'),
            'block' => __('hostel.block.block'),
            'description' => __('hostel.floor.props.description'),
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
