<?php

namespace App\Models\Exam;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Exam\AssessmentAttempt;
use App\Enums\Exam\Result as ExamResult;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Result extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'exam_results';

    protected $casts = [
        'attempt' => AssessmentAttempt::class,
        'result' => ExamResult::class,
        'total_marks' => 'float',
        'obtained_marks' => 'float',
        'percentage' => 'float',
        'generated_at' => DateTimeCast::class,
        'marks' => 'array',
        'subjects' => 'array',
        'summary' => 'array',
        'meta' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('exam_result')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
