<?php

namespace App\Imports\Inventory;

use App\Concerns\ItemImport;
use App\Models\Asset\Building\Room;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockCategory;
use App\Models\Inventory\StockItem;
use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockItemImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 100;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('stock_item');

        $errors = $this->validate($rows);

        $this->checkForErrors('stock_item', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        $stockCategories = StockCategory::query()
            ->byTeam()
            ->get();

        $rooms = Room::query()
            ->byTeam()
            ->get();

        foreach ($rows as $row) {
            $category = $stockCategories->firstWhere('name', Arr::get($row, 'category'));

            $stockItem = StockItem::forceCreate([
                'stock_category_id' => $category->id,
                'name' => Arr::get($row, 'name'),
                'code' => Arr::get($row, 'code'),
                'unit' => Arr::get($row, 'unit'),
                'description' => Arr::get($row, 'description'),
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);

            if (Arr::get($row, 'room_number')) {
                $room = $rooms->firstWhere('number', Arr::get($row, 'room_number'));

                StockBalance::forceCreate([
                    'stock_item_id' => $stockItem->id,
                    'place_type' => 'Room',
                    'place_id' => $room->id,
                    'current_quantity' => Arr::get($row, 'quantity', 0),
                ]);
            }
        }

        $team = Team::query()
            ->whereId(auth()->user()->current_team_id)
            ->first();

        $meta = $team->meta ?? [];
        $imports['stock_item'] = Arr::get($meta, 'imports.stock_item', []);
        $imports['stock_item'][] = [
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
        $items = StockItem::byTeam()->get();
        $existingNames = $items->pluck('name')->all();
        $existingCodes = $items->pluck('code')->all();

        $categories = StockCategory::query()
            ->byTeam()
            ->get();

        $rooms = Room::query()
            ->byTeam()
            ->get();

        $errors = [];

        $newNames = [];
        $newCodes = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $code = Arr::get($row, 'code');
            $unit = Arr::get($row, 'unit');
            $roomNumber = Arr::get($row, 'room_number');
            $quantity = Arr::get($row, 'quantity', 0);
            $category = Arr::get($row, 'category');
            $description = Arr::get($row, 'description');

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.name'), 'required');
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.name'), 'min_max', ['min' => 2, 'max' => 100]);
            } elseif (in_array($name, $existingNames)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.name'), 'exists');
            } elseif (in_array($name, $newNames)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.name'), 'duplicate');
            }

            if (! $code) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.code'), 'required');
            } elseif (strlen($code) < 1 || strlen($code) > 50) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.code'), 'min_max', ['min' => 2, 'max' => 50]);
            } elseif (in_array($code, $existingCodes)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.code'), 'exists');
            } elseif (in_array($code, $newCodes)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.code'), 'duplicate');
            }

            if (! $unit) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.unit'), 'required');
            }

            if (! in_array($category, $categories->pluck('name')->all())) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_category.stock_category'), 'invalid');
            }

            if ($roomNumber) {
                if (! $rooms->firstWhere('number', $roomNumber)) {
                    $errors[] = $this->setError($rowNo, trans('asset.building.room.room'), 'invalid');
                }

                if (! is_numeric($quantity)) {
                    $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.quantity'), 'numeric');
                }

                if ($quantity < 1) {
                    $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.quantity'), 'min', ['min' => 1]);
                }
            }

            if ($description && (strlen($description) < 2 || strlen($description) > 100)) {
                $errors[] = $this->setError($rowNo, trans('inventory.stock_item.props.description'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            $newNames[] = $name;
            $newCodes[] = $code;
        }

        return $errors;
    }
}
