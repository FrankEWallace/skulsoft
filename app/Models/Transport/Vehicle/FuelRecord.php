<?php

namespace App\Models\Transport\Vehicle;

use App\Casts\DateCast;
use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Transport\Vehicle\FuelType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FuelRecord extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'vehicle_fuel_records';

    protected $casts = [
        'fuel_type' => FuelType::class,
        'date' => DateCast::class,
        'price_per_unit' => PriceCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'VehicleFuelRecord';
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
            ->getOrFail(trans('transport.vehicle.fuel_record.fuel_record'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vehicle_fuel_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
