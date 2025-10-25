<?php

namespace App\Jobs\Student;

use App\Concerns\SetConfigForJob;
use App\Models\Student\Attendance;
use App\Models\Student\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Throwable;

class SendBatchAttendanceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SetConfigForJob;

    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $teamId = Arr::get($this->params, 'team_id');

        $this->setConfig($teamId);

        $attendance = Attendance::query()
            ->findOrFail(Arr::get($this->params, 'attendance_id'));

        $availableCodes = [
            ['code' => 'P', 'label' => trans('student.attendance.types.present')],
            ['code' => 'A', 'label' => trans('student.attendance.types.absent')],
            ['code' => 'L', 'label' => trans('student.attendance.types.late')],
            ['code' => 'HD', 'label' => trans('student.attendance.types.half_day')],
            ['code' => 'EL', 'label' => trans('student.attendance.types.early_leaving')],
        ];

        $jobs = [];

        Student::query()
            ->summary($teamId)
            ->where('students.batch_id', $attendance->batch_id)
            ->where(function ($q) use ($attendance) {
                $q->whereNull('students.end_date')
                  ->orWhere('students.end_date', '>=', $attendance->date->value);
            })
            ->chunk(100, function ($studentsChunk) use (&$jobs, $availableCodes, $attendance, $teamId) {
                foreach ($studentsChunk as $student) {
                    $studentAttendance = $attendance->values;

                    $attendanceCode = '';
                    foreach ($studentAttendance as $value) {
                        if (in_array($student->uuid, Arr::get($value, 'uuids', []))) {
                            $attendanceCode = Arr::get($value, 'code');
                            break;
                        }
                    }

                    $codeDetail = Arr::first($availableCodes, function ($code) use ($attendanceCode) {
                        return $code['code'] == $attendanceCode;
                    });

                    $variables = [
                        'name' => $student->name,
                        'course_name' => $student->course_name,
                        'batch_name' => $student->batch_name,
                        'date' => $attendance->date->value,
                        'attendance' => Arr::get($codeDetail, 'label', '-'),
                    ];

                    $jobs[] = new SendAttendanceNotification([
                        'email' => $student->email,
                        'contact_number' => $student->contact_number,
                        'team_id' => $teamId,
                        'variables' => $variables,
                    ]);
                }
            });

        $meta = $attendance->getMeta('notification');
        $meta['notification']['sending_at'] = now()->toDateTimeString();
        $attendance->meta = $meta;
        $attendance->save();

        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($attendance) {
                $meta = $attendance->getMeta('notification');
                $meta['notification']['sent_at'] = now()->toDateTimeString();
                $attendance->meta = $meta;
                $attendance->save();
            })
            ->catch(function (Batch $batch, Throwable $e) {
            })
            ->finally(function (Batch $batch) {
            })
            ->dispatch();
    }
}
