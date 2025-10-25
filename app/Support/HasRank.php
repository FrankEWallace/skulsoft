<?php

namespace App\Support;

trait HasRank
{
    public function updateRanking(array $data, string $key = 'marks')
    {
        return collect($data)
            ->sortByDesc($key)
            ->values()
            ->pipe(function ($collection) use ($key) {
                $rank = 1;
                $previousMark = null;
                $increment = 0;

                return $collection->map(function ($item) use (&$rank, &$previousMark, &$increment, $key) {
                    if ($previousMark !== null && $previousMark !== $item[$key]) {
                        $rank += $increment;
                        $increment = 1;
                    } else {
                        $increment++;
                    }

                    $previousMark = $item[$key];

                    return array_merge($item, ['rank' => $rank]);
                });
            })
            ->all();
    }
}
