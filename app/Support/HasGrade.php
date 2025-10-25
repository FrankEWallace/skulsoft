<?php

namespace App\Support;

use App\Models\Exam\Grade;
use Illuminate\Support\Arr;

trait HasGrade
{
    public function getGrade(Grade $grade, $maxMark, $obtainedMark, string $output = 'code')
    {
        if (! is_numeric($obtainedMark)) {
            return $obtainedMark;
        }

        if (! $maxMark || ! is_numeric($maxMark)) {
            return;
        }

        if (is_null($grade)) {
            return;
        }

        // rounding off to integer will get the grade value in case of 81-90 & 91-100
        // $percentage = $maxMark > 0 ? round(($obtainedMark / $maxMark) * 100, 2) : 0;
        $percentage = $maxMark > 0 ? round(($obtainedMark / $maxMark) * 100) : 0;

        $gradeRecord = collect($grade->records)->sortByDesc('min_score')->filter(function ($elem, $key) use ($percentage) {
            // Anything above 90 than will A1 and anything below 90 or equal to 90 will be A2
            return Arr::get($elem, 'min_score', 0) <= $percentage && Arr::get($elem, 'max_score', 0) >= $percentage;
            // return Arr::get($elem, 'min_score', 0) < $percentage && Arr::get($elem, 'max_score', 0) >= $percentage;
        })->first();

        if ($output == 'code') {
            return $gradeRecord['code'] ?? '';
        }

        return $gradeRecord;
    }
}
