<?php

namespace App\Models\Student;

use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\ServiceType;
use App\Models\Transport\Stoppage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceAllocation extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'service_allocations';

    protected $casts = [
        'type' => ServiceType::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'StudentServiceAllocation';
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function transportStoppage(): BelongsTo
    {
        return $this->belongsTo(Stoppage::class);
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('model', function ($q) use ($periodId) {
            $q->where('period_id', $periodId);
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->whereUuid($uuid)
            ->filterAccessible()
            ->with(['model' => fn ($q) => $q->basic()])
            ->getOrFail(trans('student.service_allocation.service_allocation'));
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->whereUuid($uuid)
            ->filterAccessible()
            ->with(['model' => fn ($q) => $q->detail(), 'transportStoppage'])
            ->getOrFail(trans('student.service_allocation.service_allocation'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('service_allocation')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
