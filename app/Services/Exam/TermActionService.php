<?php

namespace App\Services\Exam;

use App\Models\Exam\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TermActionService
{
    public function reorder(Request $request): void
    {
        $terms = $request->terms ?? [];

        $allTerms = Term::query()
            ->byPeriod()
            ->get();

        foreach ($terms as $index => $termItem) {
            $term = $allTerms->firstWhere('uuid', Arr::get($termItem, 'uuid'));

            if (! $term) {
                continue;
            }

            $term->position = $index + 1;
            $term->save();
        }
    }
}
