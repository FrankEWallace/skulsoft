<?php

namespace App\Models\Resource;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssignmentSubmission extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'assignment_submissions';

    protected $attributes = [];

    protected $casts = [
        'submitted_at' => DateTimeCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'AssignmentSubmission';
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('assignment_submission')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
