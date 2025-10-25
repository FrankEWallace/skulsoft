<?php

namespace App\Services\Academic;

use App\Actions\Employee\FetchEmployee;
use App\Actions\Student\FetchBatchWiseStudent;
use App\Concerns\IdCardTemplateParser;
use App\Models\Academic\IdCardTemplate;
use App\Models\Employee\Employee;
use App\Models\Guardian;
use App\Models\Student\Student;
use App\Models\Transport\RoutePassenger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IdCardService
{
    use IdCardTemplateParser;

    private function getPredefinedTemplates()
    {
        $predefinedTemplates = collect(glob(resource_path('views/print/academic/id-card/*.blade.php')))
            ->filter(function ($template) {
                return ! in_array(basename($template), ['index.blade.php']);
            })
            ->map(function ($template) {
                if (Str::contains(basename($template), 'student')) {
                    $for = 'student';
                } elseif (Str::contains(basename($template), 'employee')) {
                    $for = 'employee';
                } elseif (Str::contains(basename($template), 'guardian')) {
                    $for = 'guardian';
                } else {
                    $for = 'other';
                }

                return [
                    'name' => basename($template, '.blade.php'),
                    'for' => $for,
                    'type' => 'predefined',
                ];
            });

        return $predefinedTemplates;
    }

    private function getCustomTemplates()
    {
        $idCardTemplates = IdCardTemplate::query()
            ->byTeam()
            ->get();

        $customTemplates = collect(glob(resource_path('views/print/custom/academic/id-card/templates/*.blade.php')))
            ->filter(function ($template) {
                return ! in_array(basename($template), ['index.blade.php']);
            })
            ->map(function ($template) use ($idCardTemplates) {
                $idCardTemplate = $idCardTemplates->firstWhere('config.custom_template_file_name', basename($template, '.blade.php'));

                return [
                    'name' => basename($template, '.blade.php'),
                    'for' => $idCardTemplate?->for?->value ?? 'other',
                    'type' => 'custom',
                ];
            });

        return $customTemplates;
    }

    public function preRequisite(Request $request)
    {
        $predefinedTemplates = $this->getPredefinedTemplates();

        $customTemplates = $this->getCustomTemplates();

        $templates = collect($predefinedTemplates->merge($customTemplates))
            ->unique()
            ->map(function ($template) {
                return [
                    'label' => Str::toWord($template['name']),
                    'value' => $template['name'],
                    'for' => $template['for'],
                    'type' => 'custom',
                ];
            });

        return compact('templates');
    }

    public function print(Request $request)
    {
        $request->validate([
            'template' => ['required', 'string'],
        ]);

        $content = null;

        $predefinedTemplates = $this->getPredefinedTemplates();

        $template = collect($predefinedTemplates)->firstWhere('name', $request->template);

        if (! $template) {
            $customTemplates = $this->getCustomTemplates();

            $template = collect($customTemplates)->firstWhere('name', $request->template);
        }

        if (! $template) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('academic.id_card.template.template')])]);
        }

        if ($template['type'] == 'predefined') {
            $content = view("print.academic.id-card.{$request->template}")->render();
        } else {
            $content = view("print.custom.academic.id-card.templates.{$request->template}")->render();
        }

        $data = [];

        if (Arr::get($template, 'for') == 'student') {
            $params = [];

            if ($request->boolean('show_all_student')) {
                $params['status'] = 'all';
            }

            if ($request->batch) {
                $students = (new FetchBatchWiseStudent)->execute([
                    'batch' => $request->batch,
                    'show_detail' => true,
                    ...$params,
                ]);
            } elseif ($request->code_number) {
                $students = Student::query()
                    ->detail()
                    ->whereHas('admission', function ($q) use ($request) {
                        $q->where('code_number', $request->code_number);
                    })
                    ->get();
            }

            $transportRoutePassengers = RoutePassenger::query()
                ->with('route', 'stoppage')
                ->whereIn('model_id', $students->pluck('id'))
                ->where('model_type', 'Student')
                ->get();

            foreach ($students as $student) {
                $transportRoutePassenger = $transportRoutePassengers->firstWhere('model_id', $student->id);

                $student->route_name = $transportRoutePassenger?->route?->name;
                $student->stoppage_name = $transportRoutePassenger?->stoppage?->name;

                $data[] = $this->parse($content, $student);
            }

        } elseif (Arr::get($template, 'for') == 'guardian') {
            $params = [];

            if ($request->boolean('show_all_student')) {
                $params['status'] = 'all';
            }

            if ($request->batch) {
                $students = (new FetchBatchWiseStudent)->execute([
                    'batch' => $request->batch,
                    ...$params,
                ]);
            } elseif ($request->code_number) {
                $students = Student::query()
                    ->summary()
                    ->whereHas('admission', function ($q) use ($request) {
                        $q->where('code_number', $request->code_number);
                    })
                    ->get();
            }

            $guardians = Guardian::query()
                ->with('contact')
                ->whereIn('primary_contact_id', $students->pluck('contact_id'))
                ->get();

            $studentContactIds = Guardian::query()
                ->whereIn('contact_id', $guardians->pluck('contact_id'))
                ->get()
                ->pluck('primary_contact_id')
                ->all();

            $allGuardians = Guardian::query()
                ->whereIn('primary_contact_id', $studentContactIds)
                ->get();

            $students = Student::query()
                ->summary()
                ->whereIn('contact_id', $studentContactIds)
                ->get();

            foreach ($guardians as $guardian) {
                $allGuardian = $allGuardians->where('contact_id', $guardian->contact_id);

                $relatedStudents = $students->filter(function ($student) use ($allGuardian) {
                    return in_array($student->contact_id, $allGuardian->pluck('primary_contact_id')->all());
                });

                $guardian->related_students = $relatedStudents;

                $data[] = $this->parse($content, $guardian);
            }

        } elseif (Arr::get($template, 'for') == 'employee') {
            if ($request->department) {
                $employees = (new FetchEmployee)->execute($request);
            } else {
                $employees = Employee::query()
                    ->byTeam()
                    ->where('code_number', $request->code_number)
                    ->get();
            }

            foreach ($employees as $employee) {
                $data[] = $this->parse($content, $employee);
            }
        } else {
            // $guardians = Guardian::query()
            //     ->get();

            // foreach ($students as $student) {
            //     $data[] = $this->parse($content, $student);
            // }
        }

        $column = $request->query('column') ?? 1;
        $cardPerPage = $request->query('card_per_page') ?? 1;

        return view('print.academic.id-card.index', compact('data', 'column', 'cardPerPage'));
    }
}
