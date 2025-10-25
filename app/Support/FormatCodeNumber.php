<?php

namespace App\Support;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait FormatCodeNumber
{
    public function preFormatForDate(string $string, ?string $date = '')
    {
        if (! $date) {
            $date = today()->toDateString();
        }

        if (Str::of($string)->contains('%FINANCIAL_YEAR%')) {
            $string = str_replace('%FINANCIAL_YEAR%', config('config.general.financial_year_code'), $string);
        }

        $date = strtotime($date);

        $string = str_replace('%YEAR%', date('Y', $date), $string);
        $string = str_replace('%YEAR_SHORT%', date('y', $date), $string);
        $string = str_replace('%MONTH%', date('F', $date), $string);
        $string = str_replace('%MONTH_SHORT%', date('M', $date), $string);
        $string = str_replace('%MONTH_NUMBER%', date('m', $date), $string);
        $string = str_replace('%MONTH_NUMBER_SHORT%', date('n', $date), $string);
        $string = str_replace('%DAY%', date('d', $date), $string);
        $string = str_replace('%DAY_SHORT%', date('j', $date), $string);

        return $string;
    }

    public function preFormatForTransaction(string $string, array $params = [])
    {
        if (Str::of($string)->contains('%PAYMENT_METHOD%')) {
            $string = str_replace('%PAYMENT_METHOD%', Arr::get($params, 'payment_method'), $string);
        }

        if (Str::of($string)->contains('%LEDGER%')) {
            $string = str_replace('%LEDGER%', Arr::get($params, 'ledger'), $string);
        }

        return $string;
    }

    public function preFormatForAcademic(string $string, array $params = [])
    {
        if (Str::of($string)->contains('%PERIOD%')) {
            $string = str_replace('%PERIOD%', Arr::get($params, 'period_shortcode'), $string);
        }

        if (Str::of($string)->contains('%DEPARTMENT%')) {
            $string = str_replace('%DEPARTMENT%', Arr::get($params, 'department_shortcode'), $string);
        }

        if (Str::of($string)->contains('%PROGRAM_TYPE%')) {
            $string = str_replace('%PROGRAM_TYPE%', Arr::get($params, 'program_type_shortcode'), $string);
        }

        if (Str::of($string)->contains('%PROGRAM%')) {
            $string = str_replace('%PROGRAM%', Arr::get($params, 'program_shortcode'), $string);
        }

        if (Str::of($string)->contains('%DIVISION%')) {
            $string = str_replace('%DIVISION%', Arr::get($params, 'division_shortcode'), $string);
        }

        if (Str::of($string)->contains('%COURSE%')) {
            $string = str_replace('%COURSE%', Arr::get($params, 'course_shortcode'), $string);
        }

        return $string;
    }

    public function preFormatForAcademicCourse(int $courseId, string $string)
    {
        $params = Course::query()
            ->select(
                'courses.shortcode as course_shortcode',
                'divisions.shortcode as division_shortcode',
                'periods.shortcode as period_shortcode',
                'programs.shortcode as program_shortcode',
                'program_types.shortcode as program_type_shortcode',
                'academic_departments.shortcode as department_shortcode'
            )
            ->where('courses.id', $courseId)
            ->join('divisions', 'courses.division_id', '=', 'divisions.id')
            ->join('periods', 'divisions.period_id', '=', 'periods.id')
            ->join('programs', 'divisions.program_id', '=', 'programs.id')
            ->leftJoin('program_types', 'programs.type_id', '=', 'program_types.id')
            ->leftJoin('academic_departments', 'programs.department_id', '=', 'academic_departments.id')
            ->first()
            ?->toArray() ?? [];

        return $this->preFormatForAcademic($string, $params);
    }

    public function preFormatForAcademicBatch(int $batchId, string $string)
    {
        $params = Batch::query()
            ->select(
                'courses.shortcode as course_shortcode',
                'divisions.shortcode as division_shortcode',
                'periods.shortcode as period_shortcode',
                'programs.shortcode as program_shortcode',
                'program_types.shortcode as program_type_shortcode',
                'academic_departments.shortcode as department_shortcode'
            )
            ->where('batches.id', $batchId)
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('divisions', 'courses.division_id', '=', 'divisions.id')
            ->join('periods', 'divisions.period_id', '=', 'periods.id')
            ->join('programs', 'divisions.program_id', '=', 'programs.id')
            ->leftJoin('program_types', 'programs.type_id', '=', 'program_types.id')
            ->leftJoin('academic_departments', 'programs.department_id', '=', 'academic_departments.id')
            ->first()
            ?->toArray() ?? [];

        return $this->preFormatForAcademic($string, $params);
    }

    public function getCodeNumber(int $number = 0, int $digit = 0, string $format = ''): array
    {
        $details = [
            'code_number' => str_replace('%NUMBER%', str_pad($number, $digit, '0', STR_PAD_LEFT), $format),
            'number_format' => $format,
            'number' => $number,
            'digit' => $digit,
        ];

        if (strlen($details['code_number']) > 50) {
            throw ValidationException::withMessages([
                'message' => trans('validation.max.string', ['attribute' => trans('general.code_number'), 'max' => 50]),
            ]);
        }

        if (strlen($details['number_format']) > 50) {
            throw ValidationException::withMessages([
                'message' => trans('validation.max.string', ['attribute' => trans('general.number_format'), 'max' => 50]),
            ]);
        }

        return $details;
    }
}
