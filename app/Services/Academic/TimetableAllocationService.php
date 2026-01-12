<?php

namespace App\Services\Academic;

use App\Enums\Day;
use App\Http\Resources\Academic\SubjectResource;
use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Timetable;
use App\Domain\Academic\Models\TimetableAllocation;
use App\Domain\Academic\Models\TimetableRecord;
use App\Models\Asset\Building\Room;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TimetableAllocationService
{
    public function preRequisite(Timetable $timetable): array
    {
        $days = Day::getOptions();

        $subjects = Subject::query()
            ->withSubjectRecord($timetable->batch_id, $timetable->batch->course_id)
            ->orderBy('subjects.position', 'asc')
            ->get();

        $subjectUuids = $subjects->pluck('uuid')->all();

        $subjectIncharges = $this->getIncharges($timetable, $subjectUuids);
        $incharges = [];
        foreach ($subjectIncharges as $incharge) {
            $incharges[] = [
                'uuid' => $incharge->uuid,
                'subject_uuid' => $incharge->model->uuid,
                'employee' => EmployeeSummaryResource::make($incharge->employee),
            ];
        }

        $rooms = RoomResource::collection(Room::query()
            ->withFloorAndBlock()
            ->notAHostel()
            ->get());

        return [
            'days' => $days,
            'rooms' => $rooms,
            'incharges' => $incharges,
            'subjects' => SubjectResource::collection($subjects),
        ];
    }

    private function getIncharges(Timetable $timetable, array $subjectUuids): Collection
    {
        $subjectIncharges = Incharge::query()
            ->whereHasMorph(
                'model',
                [Subject::class],
                function (Builder $query) use ($subjectUuids) {
                    $query->byPeriod()
                        ->when($subjectUuids, function ($q, $subjectUuids) {
                            $q->whereIn('uuid', $subjectUuids);
                        });
                }
            )
            ->where(function ($q) use ($timetable) {
                $q->whereNull('detail_type')
                    ->orWhere(function ($q) use ($timetable) {
                        $q->whereNotNull('detail_type')
                            ->whereNotNull('detail_id')
                            ->whereHasMorph(
                                'detail',
                                [Batch::class],
                                function (Builder $query) use ($timetable) {
                                    $query->where('id', $timetable->batch_id);
                                }
                            );
                    });
            })
            ->with([
                'model',
                'employee' => fn ($q) => $q->summary(),
            ])
            ->get();

        return $subjectIncharges;
    }

    public function allocation(Request $request, Timetable $timetable): void
    {
        $timetableRecords = TimetableRecord::query()
            ->with('classTiming.sessions')
            ->where('timetable_id', $timetable->id)
            ->get();

        $subjects = Subject::query()
            ->withSubjectRecord($timetable->batch_id, $timetable->batch->course_id)
            ->get();

        $subjectUuids = $subjects->pluck('uuid')->all();

        $subjectIncharges = $this->getIncharges($timetable, $subjectUuids);

        $inchargeEmployeeUuids = [];
        foreach ($request->days as $dayIndex => $day) {
            $dayName = Arr::get($day, 'value');
            $timetableRecord = $timetableRecords->where('day', $dayName)->first();

            $classTiming = $timetableRecord->classTiming;

            foreach (Arr::get($day, 'sessions', []) as $sessionIndex => $session) {
                $classTimingSession = $classTiming->sessions->where('uuid', Arr::get($session, 'uuid'))->first();

                if ($classTimingSession->is_break) {
                    continue;
                }

                foreach (Arr::get($session, 'allotments', []) as $allotmentIndex => $allotment) {
                    if (Arr::get($allotment, 'employee.uuid')) {
                        $subject = $subjects->where('uuid', Arr::get($allotment, 'subject.uuid'))->first();

                        $incharges = $subjectIncharges->where('model.uuid', $subject?->uuid);

                        $employeeUuids = $incharges?->pluck('employee.uuid')?->all() ?? [];

                        $inchargeEmployeeUuids[] = Arr::get($allotment, 'employee.uuid');

                        if (! in_array(Arr::get($allotment, 'employee.uuid'), $employeeUuids)) {
                            throw ValidationException::withMessages(['days.'.$dayIndex.'.sessions.'.$sessionIndex.'.allotments.'.$allotmentIndex.'.employee' => trans('academic.timetable.employee_not_assigned_to_subject')]);
                        }
                    }
                }
            }
        }

        $employees = Employee::query()
            ->whereIn('uuid', $inchargeEmployeeUuids)
            ->get();

        \DB::beginTransaction();

        foreach ($request->days as $day) {
            $dayName = Arr::get($day, 'value');
            $timetableRecord = $timetableRecords->where('day', $dayName)->first();

            $classTiming = $timetableRecord->classTiming;

            foreach (Arr::get($day, 'sessions', []) as $sessionIndex => $session) {
                $classTimingSession = $classTiming->sessions->where('uuid', Arr::get($session, 'uuid'))->first();

                if ($classTimingSession->is_break) {
                    continue;
                }

                foreach (Arr::get($session, 'allotments', []) as $allotmentIndex => $allotment) {
                    $roomId = Arr::get($allotment, 'room_id');

                    $subject = $subjects->where('uuid', Arr::get($allotment, 'subject.uuid'))->first();

                    if (! $subject) {
                        TimetableAllocation::query()
                            ->where('timetable_record_id', $timetableRecord->id)
                            ->where('class_timing_session_id', $classTimingSession->id)
                            ->update([
                                'subject_id' => null,
                                'employee_id' => null,
                                'room_id' => null,
                            ]);

                        TimetableAllocation::query()
                            ->where('timetable_record_id', $timetableRecord->id)
                            ->where('class_timing_session_id', $classTimingSession->id)
                            ->where('meta->allotment_index', '>', 1)
                            ->delete();

                        continue;
                    }

                    $employeeId = null;
                    if (Arr::get($allotment, 'employee.uuid')) {
                        $employeeId = $employees->firstWhere('uuid', Arr::get($allotment, 'employee.uuid'))?->id;
                    }

                    $timetableAllocation = TimetableAllocation::query()
                        ->where('timetable_record_id', $timetableRecord->id)
                        ->where('class_timing_session_id', $classTimingSession->id)
                        ->when($allotmentIndex == 0, function ($q) {
                            $q->where(function ($q) {
                                $q->whereNull('meta->allotment_index')
                                    ->orWhere('meta->allotment_index', 1);
                            });
                        }, function ($q) use ($allotmentIndex) {
                            $q->where('meta->allotment_index', $allotmentIndex + 1);
                        })
                        ->first();

                    if (! $timetableAllocation) {
                        $timetableAllocation = TimetableAllocation::forceCreate([
                            'timetable_record_id' => $timetableRecord->id,
                            'class_timing_session_id' => $classTimingSession->id,
                            'subject_id' => $subject?->id,
                            'employee_id' => $employeeId,
                            'room_id' => $roomId,
                            'meta' => [
                                'allotment_index' => $allotmentIndex + 1,
                            ],
                        ]);
                    } else {
                        $timetableAllocation->subject_id = $subject?->id;
                        $timetableAllocation->employee_id = $employeeId;
                        $timetableAllocation->room_id = $roomId;
                        $timetableAllocation->setMeta([
                            'allotment_index' => $allotmentIndex + 1,
                        ]);
                        $timetableAllocation->save();
                    }
                }
            }
        }

        \DB::commit();
    }
}
