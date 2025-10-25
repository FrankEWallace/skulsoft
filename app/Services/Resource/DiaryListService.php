<?php

namespace App\Services\Resource;

use App\Contracts\ListGenerator;
use App\Http\Resources\Resource\DiaryGroupResource;
use App\Http\Resources\Resource\DiaryResource;
use App\Models\Resource\Diary;
use App\Models\Student\Student;
use App\Support\HasFilterByAssignedSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class DiaryListService extends ListGenerator
{
    use HasFilterByAssignedSubject;

    protected $allowedSorts = ['created_at', 'date'];

    protected $defaultSort = 'date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'date',
                'label' => trans('resource.diary.props.date'),
                'print_label' => 'date.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'records',
                'label' => trans('academic.batch.batch'),
                'type' => 'array',
                'print_key' => 'batch.course.name,batch.name',
                'print_sub_key' => 'subject.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'employee',
                'label' => trans('employee.employee'),
                'print_label' => 'employee.name',
                'print_sub_label' => 'employee.code_number',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'createdAt',
                'label' => trans('general.created_at'),
                'print_label' => 'created_at.formatted',
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        $employees = Str::toArray($request->query('employees'));
        $subjects = Str::toArray($request->query('subjects'));
        $batches = Str::toArray($request->query('batches'));

        $filterSubjectUuids = $this->getFilteredSubjects();

        return Diary::query()
            ->with([
                'records.subject',
                'records.batch.course',
                'employee' => fn ($q) => $q->summary(),
            ])
            ->byPeriod()
            ->withUserId()
            ->filterAccessible()
            ->when($employees, function ($q, $employees) {
                $q->whereHas('employee', function ($q) use ($employees) {
                    $q->whereIn('uuid', $employees);
                });
            })
            ->when($subjects, function ($q, $subjects) {
                $q->whereHas('records', function ($q) use ($subjects) {
                    $q->whereHas('subject', function ($q) use ($subjects) {
                        $q->whereIn('uuid', $subjects);
                    });
                });
            })
            ->when($filterSubjectUuids, function ($q, $filterSubjectUuids) {
                $q->whereHas('records', function ($q) use ($filterSubjectUuids) {
                    $q->whereHas('subject', function ($q) use ($filterSubjectUuids) {
                        $q->whereIn('uuid', $filterSubjectUuids);
                    });
                });
            })
            ->when($batches, function ($q, $batches) {
                $q->whereHas('records', function ($q) use ($batches) {
                    $q->whereHas('batch', function ($q) use ($batches) {
                        $q->whereIn('uuid', $batches);
                    });
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
                'App\QueryFilters\DateBetween:start_date,end_date,date',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        if (auth()->user()->is_student_or_guardian) {

            $students = Student::query()
                ->byPeriod()
                ->record()
                ->filterForStudentAndGuardian()
                ->get();

            $batches = Str::toArray($request->query('batches'));

            $query = Diary::query()
                ->select(
                    'student_diaries.date',
                    'batches.uuid as batch_uuid',
                    \DB::raw('CONCAT(courses.name, " ", batches.name) as course_batch'))
                ->join('batch_subject_records', function ($join) {
                    $join->on('student_diaries.id', '=', 'batch_subject_records.model_id')
                        ->where('batch_subject_records.model_type', '=', 'StudentDiary');
                })
                ->join('batches', 'batches.id', '=', 'batch_subject_records.batch_id')
                ->join('courses', 'courses.id', '=', 'batches.course_id')
                ->byPeriod()
                ->filterAccessible()
                ->whereIn('batches.id', $students->pluck('batch_id')->all())
                ->when($batches, function ($q, $batches) {
                    $q->whereIn('batches.uuid', $batches);
                })
                ->filter([
                    'App\QueryFilters\DateBetween:start_date,end_date,date',
                ]);

            $view = $request->query('view', 'card');
            $request->merge(['view' => $view]);

            return DiaryGroupResource::collection($query
                ->groupBy('date')
                ->groupBy('batch_uuid')
                ->orderBy('date', $this->getOrder())
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

        return DiaryResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
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
