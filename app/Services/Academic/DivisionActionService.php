<?php

namespace App\Services\Academic;

use App\Models\Academic\Division;
use App\Models\Academic\Period;
use App\Models\Guardian;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class DivisionActionService
{
    public function updateConfig(Request $request, Division $division): void
    {
        //
    }

    public function reorder(Request $request): void
    {
        $divisions = $request->divisions ?? [];

        $allDivisions = Division::query()
            ->byPeriod()
            ->get();

        foreach ($divisions as $index => $divisionItem) {
            $division = $allDivisions->firstWhere('uuid', Arr::get($divisionItem, 'uuid'));

            if (! $division) {
                continue;
            }

            $division->position = $index + 1;
            $division->save();
        }
    }

    public function updateCurrentPeriod(Request $request, Division $division): void
    {
        $period = Period::query()
            ->byTeam()
            ->whereId($request->period_id)
            ->first();

        if (! $period) {
            throw ValidationException::withMessages([
                'message' => trans('global.could_not_find', ['attribute' => trans('academic.period.period')]),
            ]);
        }

        $students = Student::query()
            ->select('students.id', 'contacts.id as contact_id', 'users.id as user_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('users', 'contacts.user_id', '=', 'users.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->where('courses.division_id', $division->id)
            ->get();

        $guardians = Guardian::query()
            ->select('guardians.id', 'contacts.id as contact_id', 'users.id as user_id')
            ->join('contacts', 'guardians.contact_id', '=', 'contacts.id')
            ->join('users', 'contacts.user_id', '=', 'users.id')
            ->whereIn('primary_contact_id', $students->pluck('contact_id')->all())
            ->get();

        $userIds = array_merge(
            $students->pluck('user_id')->all(),
            $guardians->pluck('user_id')->all()
        );

        $userIds = array_unique($userIds);

        $users = User::query()
            ->whereIn('id', $userIds)
            ->chunk(20, function ($users) use ($request) {
                $users->each(function (User $user) use ($request) {
                    $preference = $user->preference;
                    $preference['academic']['period_id'] = $request->period_id;
                    $user->preference = $preference;
                    $user->save();
                });
            });

        $meta = $division->meta;
        $meta['period_history'][] = [
            'name' => $period->name,
            'datetime' => now()->toDateTimeString(),
        ];

        $division->meta = $meta;
        $division->save();
    }
}
