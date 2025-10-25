<?php

namespace App\Http\Controllers\Reports;

use App\Models\Academic\Batch;
use App\Models\Exam\Exam;
use App\Models\Exam\Schedule;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ExamMarkController
{
    public function __invoke(Request $request)
    {
        $exam = Exam::query()
            ->byPeriod()
            ->findOrFail($request->query('exam_id'));

        $batches = Batch::query()
            ->with('course')
            ->byPeriod()
            ->get();

        $incharges = Incharge::query()
            ->whereHasMorph(
                'model',
                [Batch::class],
                function (Builder $query) {
                    $query->whereNotNull('id');
                }
            )
            ->with(['employee' => fn ($q) => $q->summary()])
            ->get();

        $data = [];
        foreach ($batches as $batch) {
            $batchIncharge = $incharges
                ->where('model_id', $batch->id)
                ->first();

            $schedule = Schedule::query()
                ->with('records:id,schedule_id,config,subject_id', 'records.subject:id,name')
                ->where('batch_id', $batch->id)
                ->where('exam_id', $exam->id)
                ->first();

            $subjects = [];
            foreach ($schedule->records as $record) {
                if ($record->getConfig('has_exam') && ! $record->getConfig('mark_recorded')) {
                    $subjects[] = $record->subject->name;
                }
            }

            if ($subjects) {
                $data[] = [
                    'batch' => $batch->course->name.' '.$batch->name,
                    'subjects' => implode(', ', $subjects),
                    'incharge' => $batchIncharge->employee->name,
                ];
            }
        }

        return view('reports.exam.mark', compact('exam', 'data'));
    }
}
