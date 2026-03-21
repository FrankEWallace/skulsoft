<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Academic\Models\Batch;
use App\Models\Incharge;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentProfileController
{
    public function __invoke(Request $request)
    {
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

        $batchIds = $batches->pluck('id')->all();

        // Fetch all per-batch student profile stats in a single aggregated query
        // instead of running 11 separate queries for each batch (N+1 problem).
        $stats = Student::query()
            ->select([
                'students.batch_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN contacts.email IS NULL THEN 1 ELSE 0 END) as missing_email'),
                // alternate_records is a JSON column; contact_number lives at the root level: {"contact_number": "..."}
                DB::raw("SUM(CASE WHEN contacts.alternate_records IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(contacts.alternate_records, '$.contact_number')) = '' THEN 1 ELSE 0 END) as missing_alternate_number"),
                DB::raw('SUM(CASE WHEN contacts.photo IS NULL THEN 1 ELSE 0 END) as missing_photo'),
                DB::raw("SUM(CASE WHEN contacts.unique_id_number1 IS NULL OR contacts.unique_id_number1 = '' THEN 1 ELSE 0 END) as missing_unique_id_number1"),
                DB::raw("SUM(CASE WHEN contacts.unique_id_number2 IS NULL OR contacts.unique_id_number2 = '' THEN 1 ELSE 0 END) as missing_unique_id_number2"),
                DB::raw("SUM(CASE WHEN contacts.unique_id_number3 IS NULL OR contacts.unique_id_number3 = '' THEN 1 ELSE 0 END) as missing_unique_id_number3"),
                DB::raw("SUM(CASE WHEN contacts.unique_id_number4 IS NULL OR contacts.unique_id_number4 = '' THEN 1 ELSE 0 END) as missing_unique_id_number4"),
                DB::raw("SUM(CASE WHEN contacts.unique_id_number5 IS NULL OR contacts.unique_id_number5 = '' THEN 1 ELSE 0 END) as missing_unique_id_number5"),
                DB::raw('SUM(CASE WHEN contacts.caste_id IS NULL THEN 1 ELSE 0 END) as missing_caste'),
                DB::raw('SUM(CASE WHEN contacts.category_id IS NULL THEN 1 ELSE 0 END) as missing_category'),
                DB::raw('SUM(CASE WHEN contacts.religion_id IS NULL THEN 1 ELSE 0 END) as missing_religion'),
            ])
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->whereNull('students.end_date')
            ->whereIn('students.batch_id', $batchIds)
            ->groupBy('students.batch_id')
            ->get()
            ->keyBy('batch_id');

        $data = [];
        foreach ($batches as $batch) {
            $batchStats = $stats->get($batch->id);

            $batchIncharge = $incharges
                ->where('model_id', $batch->id)
                ->first();

            $data[] = [
                'batch' => $batch->course->name.' '.$batch->name,
                'total' => $batchStats?->total ?? 0,
                'missing_email' => $batchStats?->missing_email ?? 0,
                'missing_alternate_number' => $batchStats?->missing_alternate_number ?? 0,
                'missing_photo' => $batchStats?->missing_photo ?? 0,
                'missing_caste' => $batchStats?->missing_caste ?? 0,
                'missing_category' => $batchStats?->missing_category ?? 0,
                'missing_religion' => $batchStats?->missing_religion ?? 0,
                'missing_unique_id_number1' => $batchStats?->missing_unique_id_number1 ?? 0,
                'missing_unique_id_number2' => $batchStats?->missing_unique_id_number2 ?? 0,
                'missing_unique_id_number3' => $batchStats?->missing_unique_id_number3 ?? 0,
                'missing_unique_id_number4' => $batchStats?->missing_unique_id_number4 ?? 0,
                'missing_unique_id_number5' => $batchStats?->missing_unique_id_number5 ?? 0,
                // 'subjects' => implode(', ', $subjects),
                'incharge' => $batchIncharge?->employee?->name,
            ];
        }

        return view('reports.student.profile', compact('data'));
    }
}
