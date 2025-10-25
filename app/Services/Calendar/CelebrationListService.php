<?php

namespace App\Services\Calendar;

use App\Contracts\ListGenerator;
use App\Http\Resources\Calendar\CelebrationResource;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CelebrationListService extends ListGenerator
{
    protected $allowedSorts = ['birth_date'];

    protected $defaultSort = 'birth_date';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('contact.props.name'),
                'print_label' => 'name',
                'print_sub_label' => 'contact_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'course',
                'label' => trans('academic.course.course'),
                'print_label' => 'course_name + batch_name',
                // 'print_sub_label' => 'batch_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'parent',
                'label' => trans('student.props.parent'),
                'print_label' => 'father_name',
                'print_sub_label' => 'mother_name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'contactNumber',
                'label' => trans('contact.props.contact_number'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'birthDate',
                'label' => trans('contact.props.birth_date'),
                'print_label' => 'birth_date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        // if (request()->ajax()) {
        //     $headers[] = $this->actionHeader;
        // }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $startDate = $request->query('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->endOfWeek()->format('Y-m-d'));

        $startMonth = Carbon::parse($startDate)->month;
        $endMonth = Carbon::parse($endDate)->month;
        $startDay = Carbon::parse($startDate)->day;
        $endDay = Carbon::parse($endDate)->day;

        return Student::query()
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'contacts.first_name', 'contacts.last_name', 'contacts.father_name', 'contacts.mother_name', 'contacts.contact_number', 'contacts.birth_date', 'contacts.gender', 'admissions.code_number', 'admissions.joining_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name')
            ->byPeriod()
            ->join('contacts', function ($join) use ($startMonth, $endMonth, $startDay, $endDay) {
                $join->on('students.contact_id', '=', 'contacts.id')
                    ->whereBetween(
                        \DB::raw("CONCAT(MONTH(birth_date), '-', DAY(birth_date))"),
                        ["{$startMonth}-{$startDay}", "{$endMonth}-{$endDay}"]
                    );
            })
            ->join('admissions', function ($join) {
                $join->on('students.admission_id', '=', 'admissions.id');
            })
            ->join('batches', function ($join) {
                $join->on('students.batch_id', '=', 'batches.id')
                    ->leftJoin('courses', function ($join) {
                        $join->on('batches.course_id', '=', 'courses.id');
                    });
            });
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return CelebrationResource::collection($this->filter($request)
            ->orderByRaw('MONTH(birth_date), DAY(birth_date)')
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
