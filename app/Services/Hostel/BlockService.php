<?php

namespace App\Services\Hostel;

use App\Models\Hostel\Block;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BlockService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function create(Request $request): Block
    {
        \DB::beginTransaction();

        $block = Block::forceCreate($this->formatParams($request));

        \DB::commit();

        return $block;
    }

    private function formatParams(Request $request, ?Block $block = null): array
    {
        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'description' => $request->description,
        ];

        if (! $block) {
            $formatted['type'] = 'hostel';
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        $meta = $block?->meta ?? [];
        $meta['hostel'] = [
            'contact_number' => $request->contact_number,
            'contact_email' => $request->contact_email,
            'address' => $request->address,
        ];

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Block $block): void
    {
        \DB::beginTransaction();

        $block->forceFill($this->formatParams($request, $block))->save();

        \DB::commit();
    }

    public function deletable(Block $block): bool
    {
        $floorExists = \DB::table('floors')
            ->whereBlockId($block->id)
            ->exists();

        if ($floorExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('hostel.block.block'), 'dependency' => trans('hostel.floor.floor')])]);
        }

        return true;
    }
}
