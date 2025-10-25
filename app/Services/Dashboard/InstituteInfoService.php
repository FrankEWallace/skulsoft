<?php

namespace App\Services\Dashboard;

use App\Models\Academic\Batch;
use App\Models\Incharge;
use App\Models\Student\Student;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class InstituteInfoService
{
    public function fetch(Request $request)
    {
        $team = Team::query()
            ->where('id', auth()->user()->current_team_id)
            ->firstOrFail();

        $info = [];

        array_push($info, [
            'label' => trans('team.config.general.props.name'),
            'value' => Arr::get($team->config, 'name'),
        ]);

        if (Arr::get($team->config, 'title1')) {
            array_push($info, [
                'value' => Arr::get($team->config, 'title1'),
            ]);
        }

        if (Arr::get($team->config, 'title2')) {
            array_push($info, [
                'value' => Arr::get($team->config, 'title2'),
            ]);
        }

        if (Arr::get($team->config, 'title3')) {
            array_push($info, [
                'value' => Arr::get($team->config, 'title3'),
            ]);
        }

        array_push($info, [
            'label' => trans('team.config.general.address'),
            'value' => Arr::toAddress([
                'address_line1' => Arr::get($team->config, 'address_line1'),
                'address_line2' => Arr::get($team->config, 'address_line2'),
                'city' => Arr::get($team->config, 'city'),
                'state' => Arr::get($team->config, 'state'),
                'zipcode' => Arr::get($team->config, 'zipcode'),
                'country' => Arr::get($team->config, 'country'),
            ]),
        ]);

        array_push($info, [
            'label' => trans('team.config.general.props.email'),
            'value' => Arr::get($team->config, 'email'),
        ]);

        array_push($info, [
            'label' => trans('team.config.general.props.phone'),
            'value' => Arr::get($team->config, 'phone'),
        ]);

        array_push($info, [
            'label' => trans('team.config.general.props.website'),
            'value' => Arr::get($team->config, 'website'),
        ]);

        $incharges = [];

        for ($i = 1; $i <= 5; $i++) {
            if (Arr::get($team->config, "incharge{$i}.name")) {
                array_push($incharges, [
                    'name' => Arr::get($team->config, "incharge{$i}.name"),
                    'title' => Arr::get($team->config, "incharge{$i}.title"),
                    'email' => Arr::get($team->config, "incharge{$i}.email"),
                    'contact_number' => Arr::get($team->config, "incharge{$i}.contact_number"),
                ]);
            }
        }

        if (auth()->user()->is_student_or_guardian) {
            $students = Student::query()
                ->byPeriod()
                ->record()
                ->filterForStudentAndGuardian()
                ->orderBy('name', 'asc')
                ->get();

            $batchIncharges = Incharge::query()
                ->whereHasMorph(
                    'model',
                    [Batch::class],
                    function (Builder $query) use ($students) {
                        $query->whereIn('id', $students->pluck('batch_id')->all());
                    }
                )
                ->with('model.course')
                ->where('start_date', '<=', today()->toDateString())
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', today()->toDateString());
                })
                ->with(['employee' => fn ($q) => $q->detail()])
                ->get();

            foreach ($batchIncharges as $incharge) {
                array_unshift($incharges, [
                    'name' => $incharge->employee->name,
                    'title' => trans('academic.class_teacher').' ('.$incharge->model->course->name.' '.$incharge->model->name.')',
                    'email' => $incharge->employee->email,
                    'contact_number' => $incharge->employee->contact_number,
                ]);
            }
        }

        return compact('info', 'incharges');
    }
}
