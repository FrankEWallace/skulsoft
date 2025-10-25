<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request)
    {
        $date = today()->toDateString();

        $users = User::query()
            ->select('users.*',
                'admissions.code_number as admission_number',
                'employees.code_number as employee_number',
                'batches.name as batch_name',
                'courses.name as course_name',
                'designations.name as designation_name',
                \DB::raw('CASE
                    WHEN students.id IS NOT NULL THEN "student"
                    WHEN employees.id IS NOT NULL THEN "employee"
                    ELSE NULL
                    END as user_type')
            )
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', ['guardian']);
            })
            ->join('contacts', 'contacts.user_id', '=', 'users.id')
            ->when(auth()->user()->hasAnyRole(['student', 'guardian']), function ($query) {
                $query->join('employees as employees_table', 'employees_table.contact_id', '=', 'contacts.id');
            })
            ->leftJoin('employees', 'employees.contact_id', '=', 'contacts.id')
            ->leftJoin('students', 'students.contact_id', '=', 'contacts.id')
            ->leftJoin('admissions', 'students.admission_id', '=', 'admissions.id')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
            ->leftJoin('employee_records', function ($join) use ($date) {
                $join->on('employees.id', '=', 'employee_records.employee_id')
                    ->on('employee_records.start_date', '=', \DB::raw("(select employee_records.start_date from employee_records where employees.id = employee_records.employee_id and employee_records.start_date <= '".$date."' order by employee_records.start_date desc limit 1)"))
                    ->join('designations', 'employee_records.designation_id', '=', 'designations.id');
            })
            ->where(function ($query) {
                $query->whereNull('employees.id')
                    ->orWhere('employees.team_id', auth()->user()->current_team_id);
            })
            ->where('users.id', '!=', auth()->id())
            ->where('users.name', 'like', '%'.$request->input('query').'%')
            ->paginate(10);

        return $users->map(function ($user) {
            $detail = '';

            if ($user->user_type === 'student') {
                $detail = $user->admission_number.' - '.$user->course_name.' '.$user->batch_name;
            } elseif ($user->user_type === 'employee') {
                $detail = $user->employee_number.' - '.$user->designation_name;
            }

            return [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'self' => $user->id === auth()->id(),
                'user_type' => $user->user_type,
                'detail' => $detail,
            ];
        });
    }
}
