<?php

namespace App\Models\Transport\Vehicle;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TravelRecord extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'vehicle_travel_records';

    protected $casts = [
        'date' => DateCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'VehicleTravelRecord';
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereHas('vehicle', function ($q) {
                $q->byTeam();
            })
            ->whereUuid($uuid)
            ->getOrFail(trans('transport.vehicle.travel_record.travel_record'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vehicle_travel_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
