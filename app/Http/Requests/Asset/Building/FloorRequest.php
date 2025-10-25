<?php

namespace App\Http\Requests\Asset\Building;

use App\Models\Asset\Building\Block;
use App\Models\Asset\Building\Floor;
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
                ->notAHostel()
                ->whereUuid($this->block)
                ->getOrFail(__('asset.building.block.block'), 'block');

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
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('asset.building.floor.floor')]));
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
                $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('asset.building.floor.floor')]));
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
            'name' => __('asset.building.floor.props.name'),
            'alias' => __('asset.building.floor.props.alias'),
            'block' => __('asset.building.block.block'),
            'description' => __('asset.building.floor.props.description'),
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
