<?php

namespace App\Services\Academic;

use App\Enums\Day;
use App\Http\Resources\Academic\ClassTimingResource;
use App\Http\Resources\Academic\TimetableResource;
use App\Http\Resources\Asset\Building\RoomResource;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\ClassTiming;
use App\Domain\Academic\Models\ClassTimingSession;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Timetable;
use App\Domain\Academic\Models\TimetableAllocation;
use App\Domain\Academic\Models\TimetableRecord;
use App\Models\Asset\Building\Room;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TimetableService
{
    public function preRequisite(): array
    {
        $days = Day::getOptions();

        $classTimings = ClassTimingResource::collection(ClassTiming::query()
            ->with('sessions')
            ->byPeriod()
            ->get());

        $rooms = RoomResource::collection(Room::query()
            ->withFloorAndBlock()
            ->notAHostel()
            ->get());

        return compact('classTimings', 'days', 'rooms');
    }

    public function getDetail(Timetable $timetable)
    {
        $timetable->load(['batch.course', 'room' => fn ($q) => $q->withFloorAndBlock()]);

        $rooms = Room::query()
            ->withFloorAndBlock()
            ->notAHostel()
            ->get();

        $weekdays = Day::getOptions();

        $subjects = Subject::query()
            ->withSubjectRecord($timetable->batch_id, $timetable->batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        if (auth()->user()->hasAnyRole(['student', 'guardian'])) {
            $electiveSubjects = $subjects->where('is_elective', 1);

            $students = Student::query()
                ->byPeriod()
                ->record()
                ->filterForStudentAndGuardian()
                ->get();

            $selectedElectiveSubjects = SubjectWiseStudent::query()
                ->whereIn('student_id', $students->pluck('id')->all())
                ->whereIn('subject_id', $electiveSubjects->pluck('id')->all())
                ->get();

            $subjects = $subjects->filter(function ($subject) use ($selectedElectiveSubjects) {
                return ! $subject->is_elective || $selectedElectiveSubjects->pluck('subject_id')->contains($subject->id);
            });
        }

        $timetableRecords = TimetableRecord::query()
            ->with('classTiming.sessions')
            ->where('timetable_id', $timetable->id)
            ->get();

        $timetableAllocations = TimetableAllocation::query()
            ->WhereIn('timetable_record_id', $timetableRecords->pluck('id')->all())
            ->get();

        $employees = Employee::query()
            ->with('contact')
            ->whereIn('id', $timetableAllocations->pluck('employee_id')->all())
            ->get();

        $days = [];
        foreach ($weekdays as $day) {
            $timetableRecord = $timetableRecords->firstWhere('day', Arr::get($day, 'value'));

            if (! $timetableRecord) {
                continue;
            }

            if ($timetableRecord->is_holiday) {
                $days[] = [
                    'label' => Arr::get($day, 'label'),
                    'value' => Arr::get($day, 'value'),
                    'is_holiday' => true,
                    'sessions' => [],
                ];

                continue;
            }

            $startTime = $timetableRecord->classTiming->sessions->min('start_time');
            $endTime = $timetableRecord->classTiming->sessions->max('end_time');

            $duration = Carbon::parse($startTime->value)->diff(Carbon::parse($endTime->value));

            $sessions = [];
            foreach ($timetableRecord->classTiming->sessions as $session) {
                $timetableAllocation = $timetableAllocations
                    ->where('timetable_record_id', $timetableRecord->id)
                    ->where('class_timing_session_id', $session->id);

                $allotments = [];

                foreach ($timetableAllocation as $allocation) {
                    $subject = $subjects->firstWhere('id', $allocation->subject_id);
                    $employee = $employees->firstWhere('id', $allocation->employee_id);
                    $room = $rooms->firstWhere('id', $allocation->room_id);

                    if (auth()->user()->hasAnyRole(['student', 'guardian'])) {
                        if (! $subject) {
                            continue;
                        }
                    }

                    $allotments[] = [
                        'room' => $room?->uuid,
                        'room_name' => $room?->fullName,
                        'subject' => $subject ? [
                            'uuid' => $subject?->uuid,
                            'name' => $subject?->name,
                            'code' => $subject?->code,
                            'shortcode' => $subject?->shortcode,
                        ] : null,
                        'employee' => $employee ? [
                            'uuid' => $employee?->uuid,
                            'name' => $employee?->contact->name,
                        ] : null,
                    ];
                }

                if (empty($allotments)) {
                    $allotments[] = [
                        'room' => null,
                        'room_name' => null,
                        'subject' => null,
                        'employee' => null,
                    ];
                }

                $sessions[] = [
                    'name' => $session->name,
                    'uuid' => $session->uuid,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'duration' => $session->start_time->formatted.' - '.$session->end_time->formatted,
                    'is_break' => (bool) $session->is_break,
                    'allotments' => $allotments,
                ];
            }

            $days[] = [
                'label' => Arr::get($day, 'label'),
                'value' => Arr::get($day, 'value'),
                'is_holiday' => false,
                'duration' => $duration->h.' '.trans('list.durations.hours').' '.$duration->i.' '.trans('list.durations.minutes'),
                'period' => $startTime->formatted.' - '.$endTime->formatted,
                'sessions' => $sessions,
            ];
        }

        $timetable->has_detail = true;
        $timetable->days = $days;

        return TimetableResource::make($timetable);
    }

    public function create(Request $request): Timetable
    {
        \DB::beginTransaction();

        $timetable = Timetable::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $timetable);

        \DB::commit();

        return $timetable;
    }

    private function formatParams(Request $request, ?Timetable $timetable = null): array
    {
        $formatted = [
            'batch_id' => $request->batch_id,
            'effective_date' => $request->effective_date,
            'room_id' => $request->room_id,
            'description' => $request->description,
        ];

        if (! $timetable) {
            //
        }

        return $formatted;
    }

    public function export(Timetable $timetable)
    {
        $timetable->load('records.allocations');

        $batch = $timetable->batch;

        $subjects = Subject::query()
            ->withSubjectRecord($batch->id, $batch->course_id)
            ->get();

        $rooms = Room::query()
            ->withFloorAndBlock()
            ->notAHostel()
            ->get();

        if (auth()->user()->hasAnyRole(['student', 'guardian'])) {
            $electiveSubjects = $subjects->where('is_elective', 1);

            $students = Student::query()
                ->byPeriod()
                ->record()
                ->filterForStudentAndGuardian()
                ->get();

            $selectedElectiveSubjects = SubjectWiseStudent::query()
                ->whereIn('student_id', $students->pluck('id')->all())
                ->whereIn('subject_id', $electiveSubjects->pluck('id')->all())
                ->get();

            $subjects = $subjects->filter(function ($subject) use ($selectedElectiveSubjects) {
                return ! $subject->is_elective || $selectedElectiveSubjects->pluck('subject_id')->contains($subject->id);
            });
        }

        $hasSameClassTiming = $timetable->records->where('class_timing_id', '!=', null)->pluck('class_timing_id')->unique()->count() === 1;

        $classTimings = ClassTiming::query()
            ->with('sessions')
            ->whereIn('id', $timetable->records->pluck('class_timing_id')->all())
            ->byPeriod()
            ->get();

        $inchargeEmployeeIds = [];
        foreach ($timetable->records as $record) {
            $inchargeEmployeeIds = array_merge($inchargeEmployeeIds, $record->allocations->pluck('employee_id')->all());
        }

        $inchargeEmployeeIds = array_unique($inchargeEmployeeIds);

        $employees = Employee::query()
            ->with('contact')
            ->whereIn('id', $inchargeEmployeeIds)
            ->get();

        $days = [];
        $maxNoOfSessions = 0;
        foreach ($timetable->records as $record) {
            $classTiming = $classTimings->firstWhere('id', $record->class_timing_id);

            if (! $classTiming) {
                continue;
            }

            if ($maxNoOfSessions < $classTiming->sessions->count()) {
                $maxNoOfSessions = $classTiming->sessions->count();
            }

            $allocations = $record->allocations;

            $sessions = [];
            foreach ($classTiming->sessions as $session) {
                $allotments = $allocations->where('class_timing_session_id', $session->id);

                $newAllotments = [];
                foreach ($allotments as $allotment) {
                    $subject = $subjects->firstWhere('id', $allotment->subject_id);
                    $room = $rooms->firstWhere('id', $allotment->room_id);
                    $employee = $employees->firstWhere('id', $allotment->employee_id);

                    if (auth()->user()->hasAnyRole(['student', 'guardian'])) {
                        if (! $subject) {
                            continue;
                        }
                    }

                    $newAllotments[] = [
                        'subject' => $subject,
                        'room' => $room?->full_name,
                        'employee' => $employee?->contact?->name,
                    ];
                }

                $sessions[] = [
                    'name' => $session->name,
                    'start_time' => \Cal::time($session->start_time),
                    'end_time' => \Cal::time($session->end_time),
                    'allotments' => $newAllotments,
                    'is_break' => $session->is_break,
                ];
            }

            $days[] = [
                'day' => Day::getDetail($record->day),
                'start_time' => \Cal::time($classTiming?->sessions?->min('start_time')),
                'end_time' => \Cal::time($classTiming?->sessions?->max('end_time')),
                'is_holiday' => $record->is_holiday,
                'sessions' => $sessions,
                'filler_session' => $maxNoOfSessions - count($sessions),
            ];
        }

        $timetable->has_same_class_timing = $hasSameClassTiming;
        $timetable->room_name = $rooms->firstWhere('id', $timetable->room_id)?->full_name;

        return view('print.academic.timetable.index', compact('timetable', 'batch', 'days'));
    }

    public function exportTeacherTimetable(Request $request)
    {
        $employee = Employee::query()
            ->auth()
            ->first();

        if (! $employee) {
            abort(404, 'Unauthorized');
        }

        $subjectIncharges = Incharge::query()
            ->where('employee_id', $employee->id)
            ->where('model_type', 'Subject')
            // ->where('detail_type', 'Batch') // To allow subject incharge without batch & course
            ->get();

        $rooms = Room::query()
            ->withFloorAndBlock()
            ->notAHostel()
            ->get();

        $subjects = Subject::query()
            ->whereIn('id', $subjectIncharges->pluck('model_id')->all())
            ->get();

        $allowedBatches = $subjectIncharges->pluck('detail_id')->all();

        $timetableAllocationBatchIds = TimetableAllocation::query()
            ->select('timetables.batch_id')
            ->join('timetable_records', 'timetable_allocations.timetable_record_id', '=', 'timetable_records.id')
            ->join('timetables', 'timetable_records.timetable_id', '=', 'timetables.id')
            ->where('timetable_allocations.employee_id', $employee->id)
            ->get();

        $allowedBatches = array_merge($allowedBatches, $timetableAllocationBatchIds->pluck('batch_id')->all());

        $batches = Batch::query()
            ->with('course')
            ->byPeriod()
            ->whereIn('id', $allowedBatches)
            // ->whereIn('id', $subjectIncharges->pluck('detail_id')->all()) // To allow subject incharge without batch & course
            ->get();

        $timetables = Timetable::query()
            ->whereIn('batch_id', $batches->pluck('id')->all())
            ->get();

        $latestTimetableId = [];
        foreach ($batches as $batch) {
            $timetable = $timetables->where('batch_id', $batch->id)
                ->where('effective_date.value', '<=', today()->toDateString())
                ->sortByDesc('effective_date.value')
                ->first();

            $latestTimetableId[] = $timetable?->id;
        }

        $AllDayTimetableAllocations = TimetableAllocation::query()
            ->select('timetable_allocations.*', 'timetables.batch_id', 'timetable_records.day')
            ->join('timetable_records', 'timetable_allocations.timetable_record_id', '=', 'timetable_records.id')
            ->join('timetables', 'timetable_records.timetable_id', '=', 'timetables.id')
            ->whereIn('timetable_records.timetable_id', $latestTimetableId)
            ->whereIn('subject_id', $subjects->pluck('id')->all())
            ->get();

        $dayWiseTimetableAllocations = $AllDayTimetableAllocations->groupBy('day');

        $classTimingSessions = ClassTimingSession::query()
            ->whereIn('id', $AllDayTimetableAllocations->pluck('class_timing_session_id')->all())
            ->get();

        $days = [];
        foreach ($dayWiseTimetableAllocations as $key => $timetableAllocations) {
            $sessions = [];
            foreach ($timetableAllocations as $timetableAllocation) {
                $classTimingSession = $classTimingSessions->where('id', $timetableAllocation->class_timing_session_id)->first();

                $subject = $subjects->where('id', $timetableAllocation->subject_id)->first();

                $room = $rooms->where('id', $timetableAllocation->room_id)->first();

                $batch = $batches->where('id', $timetableAllocation->batch_id)->first();

                $sessions[] = [
                    'name' => $classTimingSession->name,
                    'start_time' => $classTimingSession->start_time,
                    'end_time' => $classTimingSession->end_time,
                    'subject' => [
                        'name' => $subject?->name,
                        'code' => $subject?->code,
                    ],
                    'room' => $room?->full_name,
                    'batch' => $batch?->course->name.' '.$batch?->name,
                ];
            }

            $days[] = [
                'day' => Day::getDetail($timetableAllocation->day),
                'sessions' => $sessions,
            ];
        }

        $employee = Employee::query()
            ->summary()
            ->where('employees.id', $employee->id)
            ->first();

        return view('print.academic.timetable.teacher', compact('employee', 'days'));
    }

    private function updateRecords(Request $request, Timetable $timetable): void
    {
        foreach ($request->records as $record) {
            $timetableRecord = TimetableRecord::firstOrCreate([
                'timetable_id' => $timetable->id,
                'day' => Arr::get($record, 'day'),
            ]);

            $timetableRecord->is_holiday = Arr::get($record, 'is_holiday', false);
            $timetableRecord->class_timing_id = Arr::get($record, 'class_timing_id');
            $timetableRecord->save();
        }
    }

    public function update(Request $request, Timetable $timetable): void
    {
        // $timetableRecords = TimetableRecord::query()
        //     ->whereTimetableId($timetable->id)
        //     ->get();

        // if (TimetableAllocation::query()
        //     ->whereIn('timetable_record_id', $timetableRecords->pluck('id')->all())
        //     ->exists()) {
        //     throw ValidationException::withMessages(['message' => trans('academic.timetable.could_not_modify_if_allocated')]);
        // }

        \DB::beginTransaction();

        $timetable->forceFill($this->formatParams($request, $timetable))->save();

        $this->updateRecords($request, $timetable);

        \DB::commit();
    }

    public function deletable(Timetable $timetable, $validate = false): ?bool
    {
        $timetableRecords = TimetableRecord::query()
            ->whereTimetableId($timetable->id)
            ->get();

        if (TimetableAllocation::query()
            ->whereIn('timetable_record_id', $timetableRecords->pluck('id')->all())
            ->exists()) {
            throw ValidationException::withMessages(['message' => trans('academic.timetable.could_not_modify_if_allocated')]);
        }

        return true;
    }
}
