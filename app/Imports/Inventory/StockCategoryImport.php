<?php

namespace App\Imports\Inventory;

use App\Concerns\ItemImport;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockCategory;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockCategoryImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 100;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('stock_category');

        $errors = $this->validate($rows);

        $this->checkForErrors('stock_category', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        $inventories = Inventory::query()
            ->byTeam()
            ->get();

        foreach ($rows as $row) {
            $inventory = $inventories->firstWhere('name', Arr::get($row, 'inventory'));

            $stockCategory = StockCategory::forceCreate([
                'inventory_id' => $inventory->id,
                'name' => Arr::get($row, 'name'),
                'description' => Arr::get($row, 'description'),
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);
        }

        $team = Team::query()
            ->whereId(auth()->user()->current_team_id)
            ->first();

        $meta = $team->meta ?? [];
        $imports['stock_category'] = Arr::get($meta, 'imports.stock_category', []);
        $imports['stock_category'][] = [
            'uuid' => $importBatchUuid,
            'total' => count($rows),
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['imports'] = $imports;
        $team->meta = $meta;
        $team->save();

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $existingNames = StockCategory::byTeam()->pluck('name')->all();

        $inventories = Inventory::query()
            ->byTeam()
            ->get();

        $errors = [];

        $newNames = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $inventory = Arr::get($row, 'inventory');
            $description = Arr::get($row, 'description');

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_category.props.name'), 'required');
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_category.props.name'), 'min_max', ['min' => 2, 'max' => 100]);
            } elseif (in_array($name, $existingNames)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_category.props.name'), 'exists');
            } elseif (in_array($name, $newNames)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_category.props.name'), 'duplicate');
            }

            if (! in_array($inventory, $inventories->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('inventory.inventory'), 'invalid');
            }

            if ($description && (strlen($description) < 2 || strlen($description) > 1000)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_category.props.description'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            $newNames[] = $name;
        }

        return $errors;
    }
}
