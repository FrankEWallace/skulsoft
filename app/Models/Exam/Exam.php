<?php

namespace App\Models\Exam;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Helpers\CalHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Exam extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'exams';

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function getConfigDetailAttribute(): array
    {
        $config = $this->config;
        $lastDate = Arr::get($config, 'exam_form_last_date');

        $isFormExpired = false;
        if (CalHelper::validateDate($lastDate) && today()->toDateString() > $lastDate) {
            $isFormExpired = true;
        }

        return [
            'exam_form_fee' => \Price::from(Arr::get($config, 'exam_form_fee', 0)),
            'exam_form_late_fee' => \Price::from(Arr::get($config, 'exam_form_late_fee', 0)),
            'exam_form_last_date' => \Cal::date($lastDate),
            'exam_form_expired' => $isFormExpired,
            'show_sno' => Arr::get($config, 'show_sno', true),
            'show_print_date_time' => Arr::get($config, 'show_print_date_time', true),
            'show_watermark' => Arr::get($config, 'show_watermark', true),
            'signatory1' => Arr::get($config, 'signatory1', trans('academic.class_teacher')),
            'signatory2' => Arr::get($config, 'signatory2', trans('academic.principal')),
            'signatory3' => Arr::get($config, 'signatory3'),
            'signatory4' => Arr::get($config, 'signatory4'),
            'first_attempt' => Arr::get($config, 'first_attempt', []),
            'second_attempt' => Arr::get($config, 'second_attempt', []),
            'third_attempt' => Arr::get($config, 'third_attempt', []),
            'fourth_attempt' => Arr::get($config, 'fourth_attempt', []),
            'fifth_attempt' => Arr::get($config, 'fifth_attempt', []),
        ];
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('exam')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
