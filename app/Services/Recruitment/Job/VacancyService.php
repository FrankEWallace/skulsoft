<?php

namespace App\Services\Recruitment\Job;

use App\Enums\Gender;
use App\Models\Recruitment\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VacancyService
{
    public function preRequisite(Request $request)
    {
        $genders = Gender::getOptions();

        $instruction = nl2br(config('config.feature.job_application_instruction'));

        return compact('genders', 'instruction');
    }

    public function list(Request $request)
    {
        $vacancies = Vacancy::query()
            ->with('team', 'records.designation')
            ->where('last_application_date', '>=', today()->toDateString())
            ->where('published_at', '<=', now()->toDateTimeString())
            ->orderBy('last_application_date', 'asc')
            ->get();

        return $vacancies->groupBy('team.name')->map(function ($vacancies, $teamName) {
            return [
                'team' => $teamName,
                'vacancies' => $vacancies->map(function ($vacancy) {
                    return [
                        'uuid' => $vacancy->uuid,
                        'code_number' => $vacancy->code_number,
                        'title' => $vacancy->title,
                        'slug' => $vacancy->slug,
                        'last_application_date' => $vacancy->last_application_date,
                        'published_at' => $vacancy->published_at,
                        'summary' => Str::summary(strip_tags($vacancy->description), 200),
                        'records' => $vacancy->records->map(function ($record) {
                            return [
                                'designation' => $record->designation->name,
                                'number_of_positions' => $record->number_of_positions,
                            ];
                        }),
                    ];
                }),
            ];
        })->values();
    }

    public function detail(Request $request, $slug)
    {
        $vacancy = Vacancy::query()
            ->with('team', 'records.designation', 'records.employmentType')
            ->where('last_application_date', '>=', today()->toDateString())
            ->where('published_at', '<=', now()->toDateTimeString())
            ->where('slug', $slug)
            ->firstOrFail();

        return [
            'uuid' => $vacancy->uuid,
            'team' => $vacancy->team->name,
            'code_number' => $vacancy->code_number,
            'title' => $vacancy->title,
            'slug' => $vacancy->slug,
            'last_application_date' => $vacancy->last_application_date,
            'published_at' => $vacancy->published_at,
            'team' => $vacancy->team->name,
            'description' => $vacancy->description,
            'responsibility' => $vacancy->responsibility,
            'records' => $vacancy->records->map(function ($record) {
                return [
                    'designation' => $record->designation->name,
                    'designation_uuid' => $record->designation->uuid,
                    'employment_type' => $record->employmentType->name,
                    'number_of_positions' => $record->number_of_positions,
                ];
            }),
        ];
    }
}
