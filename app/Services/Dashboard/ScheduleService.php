<?php

namespace App\Services\Dashboard;

use App\Enums\OptionType;
use App\Enums\Student\AttendanceSession;
use App\Helpers\CalHelper;
use App\Models\Calendar\Event;
use App\Models\Calendar\Holiday;
use App\Models\Communication\Announcement;
use App\Models\Exam\Record;
use App\Models\Mess\MealLog;
use App\Models\Resource\Assignment;
use App\Models\Resource\Diary;
use App\Models\Resource\LearningMaterial;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use App\Models\Utility\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ScheduleService
{
    public function fetch(Request $request)
    {
        $students = collect([]);
        if (auth()->user()->is_student_or_guardian) {
            $students = Student::query()
                ->byPeriod()
                ->record()
                ->filterForStudentAndGuardian()
                ->orderBy('name', 'asc')
                ->get();
        }

        $showFeed = ! $request->query('filter') || ($request->query('filter') == true && $request->query('show_feed') == true);
        $showCalendar = ! $request->query('filter') || ($request->query('filter') == true && $request->query('show_calendar') == true);

        $data = [];
        if ($showFeed) {
            $feedItems = $this->getFeed($request, $students);
            $data['feed_items'] = $feedItems;
        }

        if ($showCalendar) {
            $calendar = $this->getCalendar($request, $students);
            $data['calendar'] = $calendar;
        }

        return $data;
    }

    private function getFeed(Request $request, Collection $students)
    {
        $feedItems = [];
        $startDate = now()->subDays(7)->startofDay();
        $endDate = now()->addDays(7)->endOfDay();

        $announcements = Announcement::query()
            ->select('announcements.uuid', 'code_number', 'title', 'published_at', 'options.name as announcement_type', 'announcements.meta')
            ->leftJoin('options', function ($join) {
                $join->on('announcements.type_id', '=', 'options.id')
                    ->where('options.type', '=', OptionType::ANNOUNCEMENT_TYPE->value);
            })
            ->byPeriod()
            ->filterAccessible()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('announcements.published_at', '>=', $startDate->toDateTimeString())
                    ->where('announcements.published_at', '<=', $endDate->toDateTimeString());
            })->orWhere(function ($query) {
                $query->whereNotNull('announcements.meta->pinned_at')
                    ->where('announcements.meta->pinned_at', '>=', today()->subMonth()->toDateTimeString());
            })
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($announcements as $announcement) {
            $feedItems[] = [
                'uuid' => $announcement->uuid,
                'title' => $announcement->title,
                'sub_title' => $announcement->announcement_type,
                'is_pinned' => $announcement->getMeta('pinned_at') ? true : false,
                'type' => 'announcement',
                'color' => 'bg-info',
                'icon' => 'fas fa-bullhorn',
                'has_detail' => auth()->user()->can('announcement:read'),
                'duration' => $announcement->published_at->formatted,
                'datetime' => $announcement->published_at->value,
            ];
        }

        $events = Event::query()
            ->select('events.uuid', 'code_number', 'title', 'start_date', 'start_time', 'end_date', 'end_time', 'options.name as event_type', 'venue', 'events.meta')
            ->leftJoin('options', function ($join) {
                $join->on('events.type_id', '=', 'options.id')
                    ->where('options.type', '=', OptionType::EVENT_TYPE->value);
            })
            ->byPeriod()
            ->filterAccessible()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('events.start_date', '>=', $startDate->toDateString())
                    ->where('events.end_date', '<=', $endDate->toDateString());
            })->orWhere(function ($query) {
                $query->whereNotNull('events.meta->pinned_at')
                    ->where('events.meta->pinned_at', '>=', today()->subMonth()->toDateTimeString());
            })
            ->orderBy('events.start_date', 'desc')
            ->limit(10)
            ->get();

        foreach ($events as $event) {
            $feedItems[] = [
                'uuid' => $event->uuid,
                'title' => $event->title,
                'sub_title' => $event->event_type,
                'is_pinned' => $event->getMeta('pinned_at') ? true : false,
                'type' => 'event',
                'color' => 'bg-success',
                'icon' => 'fas fa-calendar-alt',
                'has_detail' => auth()->user()->can('event:read'),
                'duration' => $event->duration_in_detail,
                'datetime' => $event->start_date->value.' '.($event->start_time->value ?? '00:00:00'),
            ];
        }

        $assignments = Assignment::query()
            ->select('assignments.*', 'batches.name as batch_name', 'courses.name as course_name')
            ->join('batch_subject_records', function ($join) {
                $join->on('assignments.id', '=', 'batch_subject_records.model_id')
                    ->where('batch_subject_records.model_type', '=', 'Assignment');
            })
            ->join('batches', 'batches.id', '=', 'batch_subject_records.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->whereIn('batch_subject_records.batch_id', $students->pluck('batch_id')->all())
            ->where('date', today()->toDateString())
            ->get();

        foreach ($assignments as $assignment) {
            $feedItems[] = [
                'uuid' => $assignment->uuid,
                'title' => trans('resource.assignment.assignment'),
                'sub_title' => $assignment->course_name.' - '.$assignment->batch_name,
                'type' => 'assignment',
                'color' => 'bg-success',
                'icon' => 'fas fa-briefcase',
                'has_detail' => auth()->user()->can('assignment:read'),
                'duration' => $assignment->date->formatted,
                'datetime' => $assignment->date->value,
            ];
        }

        $learningMaterials = LearningMaterial::query()
            ->select('learning_materials.*', 'batches.name as batch_name', 'courses.name as course_name')
            ->join('batch_subject_records', function ($join) {
                $join->on('learning_materials.id', '=', 'batch_subject_records.model_id')
                    ->where('batch_subject_records.model_type', '=', 'LearningMaterial');
            })
            ->join('batches', 'batches.id', '=', 'batch_subject_records.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->whereIn('batch_subject_records.batch_id', $students->pluck('batch_id')->all())
            ->where('published_at', '>=', now()->subDays(2)->toDateTimeString())
            ->get();

        foreach ($learningMaterials as $learningMaterial) {
            $feedItems[] = [
                'uuid' => $learningMaterial->uuid,
                'title' => trans('resource.learning_material.learning_material'),
                'sub_title' => $learningMaterial->course_name.' - '.$learningMaterial->batch_name,
                'type' => 'learning_material',
                'color' => 'bg-success',
                'icon' => 'fas fa-note-sticky',
                'has_detail' => auth()->user()->can('learning-material:read'),
                'duration' => $learningMaterial->published_at->formatted,
                'datetime' => $learningMaterial->published_at->value,
            ];
        }

        $diaries = Diary::query()
            ->select('student_diaries.*', 'batches.uuid as batch_uuid', \DB::raw('CONCAT(courses.name, " ", batches.name) as course_batch'))
            ->join('batch_subject_records', function ($join) {
                $join->on('student_diaries.id', '=', 'batch_subject_records.model_id')
                    ->where('batch_subject_records.model_type', '=', 'StudentDiary');
            })
            ->join('batches', 'batches.id', '=', 'batch_subject_records.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->whereIn('batch_subject_records.batch_id', $students->pluck('batch_id')->all())
            ->where('date', today()->toDateString())
            ->get()
            ->groupBy(['date.value', 'batch_uuid'])
            ->all();

        foreach ($diaries as $date => $batches) {
            foreach ($batches as $batchUuid => $diary) {
                $class = Arr::get(Arr::first($diary), 'course_batch');
                $feedItems[] = [
                    'uuid' => $batchUuid,
                    'title' => trans('resource.diary.diary'),
                    'sub_title' => $class,
                    'type' => 'diary',
                    'color' => 'bg-success',
                    'icon' => 'fas fa-book',
                    'has_detail' => auth()->user()->can('student-diary:read'),
                    'duration' => \Cal::date($date)->formatted,
                    'datetime' => \Cal::date($date)->value,
                ];
            }
        }

        $examRecords = collect([]);
        if ($students->count()) {
            $examRecords = Record::query()
                ->select('exam_records.date', 'exam_records.uuid as exam_record_uuid', 'exam_schedules.uuid as exam_schedule_uuid', 'subjects.name as subject_name', 'exams.name as exam_name', 'courses.name as course_name', 'batches.name as batch_name')
                ->join('exam_schedules', 'exam_schedules.id', '=', 'exam_records.schedule_id')
                ->join('subjects', 'subjects.id', '=', 'exam_records.subject_id')
                ->join('batches', 'batches.id', '=', 'exam_schedules.batch_id')
                ->join('courses', 'courses.id', '=', 'batches.course_id')
                ->join('exams', 'exams.id', '=', 'exam_schedules.exam_id')
                ->whereIn('exam_schedules.batch_id', $students->pluck('batch_id')->all())
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();
        }

        foreach ($examRecords as $examRecord) {
            $feedItems[] = [
                'uuid' => $examRecord->exam_record_uuid,
                'schedule_uuid' => $examRecord->exam_schedule_uuid,
                'title' => $examRecord->exam_name.' - '.$examRecord->subject_name,
                'sub_title' => $examRecord->course_name.' - '.$examRecord->batch_name,
                'type' => 'exam',
                'color' => 'bg-info',
                'icon' => 'fas fa-file-alt',
                'has_detail' => auth()->user()->can('exam-schedule:read'),
                'duration' => $examRecord->date->formatted,
                'datetime' => $examRecord->date->value,
            ];
        }

        $mealLogs = MealLog::query()
            ->with('records.item')
            ->select('meal_logs.*', 'meals.name as meal_name')
            ->join('meals', 'meals.id', '=', 'meal_logs.meal_id')
            ->where('meals.team_id', auth()->user()?->current_team_id)
            ->where('date', today()->toDateString())
            ->get();

        foreach ($mealLogs as $mealLog) {
            $items = $mealLog->records->map(function ($record) {
                return ['item' => $record->item->name];
            })->pluck('item')->implode(', ');

            if ($mealLog->description) {
                $items .= ' ('.$mealLog->description.')';
            }

            $feedItems[] = [
                'uuid' => $mealLog->uuid,
                'title' => $mealLog->meal_name,
                'sub_title' => $items,
                'type' => 'meal',
                'color' => 'bg-success',
                'icon' => 'fas fa-utensils',
                'has_detail' => auth()->user()->can('meal-log:read'),
                'duration' => $mealLog->date->formatted,
                'datetime' => $mealLog->date->value,
            ];
        }

        usort($feedItems, [$this, 'compareDateTime']);

        return $feedItems;
    }

    private function compareDateTime($a, $b)
    {
        $dateTimeA = strtotime(Arr::get($a, 'datetime'));
        $dateTimeB = strtotime(Arr::get($b, 'datetime'));

        return $dateTimeB - $dateTimeA;
    }

    private function getCalendar(Request $request, Collection $students)
    {
        $type = $request->query('type');

        if ($type == 'next' && CalHelper::validateDate($request->query('next_month_date'))) {
            $date = $request->query('next_month_date');
        } elseif ($type == 'previous' && CalHelper::validateDate($request->query('last_month_date'))) {
            $date = $request->query('last_month_date');
        } elseif ($request->query('start_date')) {
            if (! CalHelper::validateDate($request->query('start_date'))) {
                $date = today()->toDateString();
            } else {
                $date = Carbon::parse($request->query('start_date'))->endOfMonth()->toDateString();
            }
        } else {
            if (! CalHelper::validateDate($request->date)) {
                $date = today()->toDateString();
            } else {
                $date = $request->date;
            }
        }

        $nextMonthDate = Carbon::parse($date)->startOfMonth()->addMonth()->toDateString();
        $lastMonthDate = Carbon::parse($date)->startOfMonth()->subDay()->toDateString();

        $startOfMonth = Carbon::parse($date)->startOfMonth();
        $endOfMonth = Carbon::parse($date)->endOfMonth();

        $startDate = $startOfMonth->isMonday() ? $startOfMonth : $startOfMonth->previous(Carbon::MONDAY);
        $endDate = $endOfMonth->isSunday() ? $endOfMonth : $endOfMonth->next(Carbon::SUNDAY);

        $holidays = Holiday::query()
            ->betweenPeriod($startDate->toDateString(), $endDate->toDateString())
            ->get();

        $todos = Todo::query()
            ->where('user_id', auth()->id())
            ->where('due_date', '>=', $startDate->toDateString())
            ->where('due_date', '<=', $endDate->toDateString())
            ->get();

        $days = [];
        while ($startDate->lte($endDate)) {
            $isToday = $startDate->isSameDay(today()->toDateString());

            $events = [];

            $currentDate = $startDate->toDateString();

            $holiday = $holidays->where('start_date.value', '<=', $currentDate)->where('end_date.value', '>=', $currentDate)->first();

            if ($holiday) {
                $events[] = [
                    'id' => (string) Str::uuid(),
                    'name' => $holiday->name,
                    'type' => 'holiday',
                    'time' => '',
                    'datetime' => '',
                    'href' => '#',
                ];
            }

            foreach ($todos->where('due_date.value', $startDate->toDateString()) as $todo) {
                $events[] = [
                    'id' => (string) Str::uuid(),
                    'name' => $todo->title,
                    'type' => 'todo',
                    'time' => '',
                    'datetime' => '',
                    'href' => '#',
                ];
            }

            $days[] = [
                'date' => $startDate->toDateString(),
                'events' => $events,
                'is_selected' => $isToday,
                'is_current_month' => $startDate->isSameMonth($date),
                'is_today' => $isToday,
            ];

            $startDate->addDay();
        }

        $days = $this->getAttendances($students, $days);

        return [
            'days' => $days,
            'month' => Carbon::parse($date)->format('F Y'),
            'last_month_date' => $lastMonthDate,
            'next_month_date' => $nextMonthDate,
        ];
    }

    private function getAttendances(Collection $students, array $days = []): array
    {
        if (! auth()->user()->is_student_or_guardian) {
            return $days;
        }

        $startDate = Arr::get(Arr::first($days), 'date');
        $endDate = Arr::get(Arr::last($days), 'date');

        $attendanceData = Attendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('batch_id', $students->pluck('batch_id')->all())
            ->whereNull('subject_id')
            ->where('session', AttendanceSession::FIRST)
            ->whereIsDefault(1)
            ->get();

        if ($attendanceData->count() == 0) {
            return $days;
        }

        return collect($days)->map(function ($day) use ($students, $attendanceData) {
            $currentDate = Arr::get($day, 'date');

            $attendances = [];
            foreach ($students as $index => $student) {
                $dayWiseAttendance = $attendanceData->where('batch_id', $student->batch_id)->where('date.value', $currentDate)->first();

                $forceHoliday = $dayWiseAttendance?->getMeta('is_holiday') ? true : false;

                $values = $dayWiseAttendance?->values ?? [];

                if ($forceHoliday) {
                    $attendance = 'H';
                } else {
                    $attendance = collect($values)->flatMap(function ($attendanceItem) use ($student) {
                        if (in_array($student->uuid, $attendanceItem['uuids'])) {
                            return [$attendanceItem['code']];
                        }
                    })->first();
                }

                match ($attendance) {
                    'H' => $attendance = ['code' => 'H', 'label' => trans('student.attendance.types.holiday'), 'color' => 'bg-info'],
                    'P' => $attendance = ['code' => $attendance, 'label' => trans('student.attendance.types.present'), 'color' => 'bg-success'],
                    'A' => $attendance = ['code' => $attendance, 'label' => trans('student.attendance.types.absent'), 'color' => 'bg-danger'],
                    'HD' => $attendance = ['code' => $attendance, 'label' => trans('student.attendance.types.half_day'), 'color' => 'bg-warning'],
                    'L' => $attendance = ['code' => $attendance, 'label' => trans('student.attendance.types.late'), 'color' => 'bg-info'],
                    default => $attendance = [],
                };

                $attendances[] = [
                    'name' => $student->name,
                    'uuid' => $student->uuid,
                    'id_number' => $index + 1,
                    ...$attendance,
                ];
            }

            return [
                'attendances' => $attendances,
                ...$day,
            ];
        })->toArray();
    }
}
