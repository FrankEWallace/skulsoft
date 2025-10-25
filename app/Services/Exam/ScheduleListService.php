<?php

namespace App\Services\Exam;

use App\Contracts\ListGenerator;
use App\Http\Resources\Exam\ScheduleResource;
use App\Models\Exam\Record;
use App\Models\Exam\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ScheduleListService extends ListGenerator
{
    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'start_date';

    protected $defaultOrder = 'desc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'exam',
                'label' => trans('exam.exam'),
                'print_label' => 'exam.name',
                'print_sub_label' => 'exam.term.division.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'batch',
                'label' => trans('academic.batch.batch'),
                'print_label' => 'batch.course.name',
                'print_sub_label' => 'batch.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'assessment',
                'label' => trans('exam.assessment.assessment'),
                'print_label' => 'assessment.name',
                'print_sub_label' => 'observation.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'grade',
                'label' => trans('exam.grade.grade'),
                'print_label' => 'grade.name',
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'period',
                'label' => trans('exam.schedule.props.period'),
                'print_label' => 'start_date.formatted',
                'print_sub_label' => 'end_date.formatted',
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
        $exams = Str::toArray($request->query('exams'));
        $batches = Str::toArray($request->query('batches'));

        $isStudentOrGuardian = auth()->user()->is_student_or_guardian;

        return Schedule::query()
            ->with('batch.course', 'exam.term.division', 'grade', 'assessment', 'observation')
            ->byPeriod()
            ->filterAccessible()
            ->when($isStudentOrGuardian, function ($q) {
                $q->with(['records:id,uuid,schedule_id,subject_id,date,start_time,duration,config', 'records.subject:id,uuid,name,code']);
            })
            ->addSelect(['start_date' => Record::select('date')
                ->whereColumn('schedule_id', 'exam_schedules.id')
                ->whereNotNull('date')
                ->orderBy('date', 'asc')
                ->limit(1),
            ])
            ->addSelect(['end_date' => Record::select('date')
                ->whereColumn('schedule_id', 'exam_schedules.id')
                ->whereNotNull('date')
                ->orderBy('date', 'desc')
                ->limit(1),
            ])
            ->when($exams, function ($q, $exams) {
                $q->whereHas('exam', function ($q) use ($exams) {
                    $q->whereIn('uuid', $exams);
                });
            })
            ->when($batches, function ($q, $batches) {
                $q->whereHas('batch', function ($q) use ($batches) {
                    $q->whereIn('uuid', $batches);
                });
            })
            ->filter([
                'App\QueryFilters\UuidMatch',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return ScheduleResource::collection($this->filter($request)
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
