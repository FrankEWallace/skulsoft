<?php

namespace App\Actions\Academic;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use App\Models\Academic\Department;
use App\Models\Academic\Division;
use App\Models\Academic\Period;
use App\Models\Academic\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AcademicSeeder
{
    public function execute(Request $request, Period $period): void
    {
        if (! $request->seeders) {
            return;
        }

        $file = resource_path('var/academic-seeder.json');
        $seeder = (\File::exists($file)) ? \File::json($file) : [];

        $departments = collect($seeder)->whereIn('code', $request->seeders);

        if (! $departments) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.department.department')])]);
        }

        foreach ($departments as $department) {
            $newDepartment = Department::query()
                ->firstOrCreate([
                    'name' => $department['name'],
                    'team_id' => auth()->user()->current_team_id,
                ]);

            $programs = Arr::get($department, 'programs', []);

            foreach ($programs as $program) {
                $newProgram = Program::query()
                    ->firstOrCreate([
                        'name' => $program['name'],
                        'team_id' => auth()->user()->current_team_id,
                        'department_id' => $newDepartment->id,
                    ]);

                $divisions = Arr::get($program, 'divisions', []);

                foreach ($divisions as $division) {
                    $newDivision = Division::forceCreate([
                        'name' => Arr::get($division, 'name'),
                        'description' => Arr::get($division, 'description'),
                        'program_id' => $newProgram->id,
                        'period_id' => $period->id,
                    ]);

                    $courses = Arr::get($division, 'courses', []);

                    foreach ($courses as $course) {
                        $newCourse = Course::forceCreate([
                            'name' => Arr::get($course, 'name'),
                            'description' => Arr::get($course, 'description'),
                            'division_id' => $newDivision->id,
                        ]);

                        $batches = Arr::get($course, 'batches', []);

                        foreach ($batches as $batch) {
                            $newBatch = Batch::forceCreate([
                                'name' => Arr::get($batch, 'name'),
                                'description' => Arr::get($batch, 'description'),
                                'course_id' => $newCourse->id,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
