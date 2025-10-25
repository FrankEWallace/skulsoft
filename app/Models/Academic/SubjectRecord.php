<?php

namespace App\Models\Academic;

use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubjectRecord extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'subject_records';

    protected $attributes = [];

    protected $casts = [
        'credit' => 'float',
        'max_class_per_week' => 'integer',
        'exam_fee' => PriceCast::class,
        'course_fee' => PriceCast::class,
        'is_elective' => 'boolean',
        'has_no_exam' => 'boolean',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
    }

    public function scopeWithCurrentIncharges(Builder $query)
    {
        $query->with([
            'incharges' => function ($q) {
                return $q->where('start_date', '<=', today()->toDateString())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', today()->toDateString());
                    });
            }, 'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithLastIncharge(Builder $query)
    {
        $query->addSelect(['incharge_id' => Incharge::select('id')
            ->whereColumn('model_id', 'subject_records.id')
            ->where('model_type', 'SubjectRecord')
            ->where('effective_date', '<=', today()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('subject', function ($q) use ($periodId) {
            $q->byPeriod($periodId);
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, $subjectId, $uuid)
    {
        return $query
            ->byPeriod()
            ->where('subject_id', $subjectId)
            ->where('uuid', $uuid)
            ->getOrFail(trans('academic.subject.allocation'), 'message');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('subject_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
