<?php

namespace App\Services\Finance;

use App\Http\Resources\Finance\LedgerTypeResource;
use App\Models\Finance\LedgerType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LedgerTypeService
{
    public function preRequisite(): array
    {
        $types = LedgerTypeResource::collection(LedgerType::query()
            ->byTeam()
            ->get());

        return compact('types');
    }

    public function create(Request $request): LedgerType
    {
        \DB::beginTransaction();

        $ledgerType = LedgerType::forceCreate($this->formatParams($request));

        \DB::commit();

        return $ledgerType;
    }

    private function formatParams(Request $request, ?LedgerType $ledgerType = null): array
    {
        $parent = $request->parent;

        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'parent_id' => $parent?->id,
            'type' => $parent?->type?->value,
            'description' => $request->description,
        ];

        if (! $ledgerType) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function isEditable(LedgerType $ledgerType)
    {
        if ($ledgerType->is_default) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_modify_default', ['attribute' => trans('finance.ledger_type.ledger_type')])]);
        }
    }

    public function update(LedgerType $ledgerType, Request $request): void
    {
        $this->isEditable($ledgerType);

        if ($ledgerType->ledgers()->count()) {
            if ($request->parent != $ledgerType->parent->uuid) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_modify', ['attribute' => trans('finance.ledger_type.props.parent')])]);
            }
        }

        \DB::beginTransaction();

        $ledgerType->forceFill($this->formatParams($request, $ledgerType))->save();

        \DB::commit();
    }

    public function deletable(LedgerType $ledgerType): void
    {
        $this->isEditable($ledgerType);

        $ledgerExists = \DB::table('ledgers')
            ->whereLedgerTypeId($ledgerType->id)
            ->exists();

        if ($ledgerExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.ledger_type.ledger_type'), 'dependency' => trans('finance.ledger.ledger')])]);
        }
    }
}
