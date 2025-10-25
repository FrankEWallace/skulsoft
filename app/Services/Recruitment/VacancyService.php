<?php

namespace App\Services\Recruitment;

use App\Enums\OptionType;
use App\Http\Resources\Employee\DesignationResource;
use App\Http\Resources\OptionResource;
use App\Models\Employee\Designation;
use App\Models\Option;
use App\Models\Recruitment\Vacancy;
use App\Models\Recruitment\VacancyRecord;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class VacancyService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.recruitment.vacancy_number_prefix');
        $numberSuffix = config('config.recruitment.vacancy_number_suffix');
        $digit = config('config.recruitment.vacancy_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) Vacancy::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $designations = DesignationResource::collection(Designation::query()
            ->byTeam()
            ->get());

        $employmentTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::EMPLOYMENT_TYPE->value)
            ->get());

        return compact('designations', 'employmentTypes');
    }

    public function create(Request $request): Vacancy
    {
        \DB::beginTransaction();

        $vacancy = Vacancy::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $vacancy);

        $vacancy->addMedia($request);

        \DB::commit();

        return $vacancy;
    }

    private function formatParams(Request $request, ?Vacancy $vacancy = null): array
    {
        $formatted = [
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'last_application_date' => $request->last_application_date,
            'description' => clean($request->description),
            'responsibility' => clean($request->responsibility),
        ];

        if (! $vacancy) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');

            $formatted['published_at'] = now()->toDateTimeString();
            $formatted['team_id'] = auth()->user()->current_team_id;
        }

        return $formatted;
    }

    private function updateRecords(Request $request, Vacancy $vacancy): void
    {
        $records = [];
        foreach ($request->records as $record) {
            $vacancyRecord = VacancyRecord::firstOrCreate([
                'vacancy_id' => $vacancy->id,
                'designation_id' => Arr::get($record, 'designation_id'),
                'employment_type_id' => Arr::get($record, 'employment_type_id'),
            ]);

            $records[] = $vacancyRecord->id;

            $vacancyRecord->number_of_positions = Arr::get($record, 'number_of_positions');
            $vacancyRecord->save();
        }

        VacancyRecord::query()
            ->whereVacancyId($vacancy->id)
            ->whereNotIn('id', $records)
            ->delete();
    }

    public function update(Request $request, Vacancy $vacancy): void
    {
        \DB::beginTransaction();

        $vacancy->forceFill($this->formatParams($request, $vacancy))->save();

        $this->updateRecords($request, $vacancy);

        $vacancy->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Request $request, Vacancy $vacancy): void
    {
        //
    }
}
