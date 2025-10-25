<?php

namespace App\Services\Library;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Library\BookAddition;
use App\Models\Library\BookCopy;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BookAdditionService
{
    public function preRequisite(Request $request)
    {
        $conditions = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::BOOK_CONDITION->value)
            ->get());

        return compact('conditions');
    }

    public function create(Request $request): BookAddition
    {
        \DB::beginTransaction();

        $bookAddition = BookAddition::forceCreate($this->formatParams($request));

        $this->updateCopies($request, $bookAddition);

        \DB::commit();

        return $bookAddition;
    }

    private function updateCopies(Request $request, BookAddition $bookAddition): void
    {
        $bookNumbers = [];
        foreach ($request->copies as $copy) {
            $bookNumbers[] = Arr::get($copy, 'number');

            $bookCopy = BookCopy::firstOrCreate([
                'book_addition_id' => $bookAddition->id,
                'number' => Arr::get($copy, 'number'),
            ]);

            $bookCopy->book_id = Arr::get($copy, 'book_id');
            $bookCopy->uuid = Arr::get($copy, 'uuid');
            $bookCopy->condition_id = Arr::get($copy, 'condition_id');
            $bookCopy->vendor = Arr::get($copy, 'vendor');
            $bookCopy->invoice_number = Arr::get($copy, 'invoice_number');
            $bookCopy->invoice_date = Arr::get($copy, 'invoice_date') ?: null;
            $bookCopy->room_number = Arr::get($copy, 'room_number');
            $bookCopy->rack_number = Arr::get($copy, 'rack_number');
            $bookCopy->shelf_number = Arr::get($copy, 'shelf_number');
            $bookCopy->remarks = Arr::get($copy, 'remarks');
            $bookCopy->save();
        }

        BookCopy::query()
            ->whereBookAdditionId($bookAddition->id)
            ->whereNotIn('number', $bookNumbers)
            ->delete();
    }

    private function formatParams(Request $request, ?BookAddition $bookAddition = null): array
    {
        $formatted = [
            'date' => $request->date,
            'remarks' => $request->remarks,
        ];

        if (! $bookAddition) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, BookAddition $bookAddition): void
    {
        \DB::beginTransaction();

        $bookAddition->forceFill($this->formatParams($request, $bookAddition))->save();

        $this->updateCopies($request, $bookAddition);

        \DB::commit();
    }

    public function deletable(BookAddition $bookAddition): void
    {
        //
    }
}
