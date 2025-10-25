<?php

namespace App\Models\Transport;

use App\Casts\TimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Transport\Direction;
use App\Models\Academic\Period;
use App\Models\Transport\Vehicle\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Route extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transport_routes';

    protected $attributes = [];

    protected $casts = [
        'direction' => Direction::class,
        'arrival_starts_at' => TimeCast::class,
        'departure_starts_at' => TimeCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function routeStoppages(): HasMany
    {
        return $this->hasMany(RouteStoppage::class, 'route_id');
    }

    public function routePassengers()
    {
        return $this->hasMany(RoutePassenger::class, 'route_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function getArrivalStoppageTimings(): array
    {
        if (! $this->relationLoaded('routeStoppages')) {
            return [];
        }

        $startTime = Carbon::parse($this->arrival_starts_at->value);
        $routeStoppages = $this->routeStoppages->sortBy('position');

        $stoppages = [];

        foreach ($routeStoppages as $routeStoppage) {
            $arrivalTime = (int) $routeStoppage->arrival_time;

            $stoppageArrivalTime = $startTime->addMinutes($arrivalTime);

            $stoppages[] = [
                'name' => $routeStoppage->stoppage->name,
                'arrival_time' => \Cal::time($stoppageArrivalTime),
            ];
        }

        return $stoppages;
    }

    public function getDepartureStoppageTimings(): array
    {
        if (! $this->relationLoaded('routeStoppages')) {
            return [];
        }

        if ($this->direction == Direction::DEPARTURE) {
            $startTime = Carbon::parse($this->departure_starts_at->value);
            $routeStoppages = $this->routeStoppages;
        } else {
            $startTime = Carbon::parse($this->departure_starts_at->value);
            $startTime->addMinutes((int) $this->duration_to_destination);
            $routeStoppages = $this->routeStoppages->sortByDesc('position');
        }

        $stoppages = [];

        foreach ($routeStoppages as $routeStoppage) {
            $arrivalTime = (int) $routeStoppage->arrival_time;

            if ($this->direction == Direction::DEPARTURE) {
                $stoppageArrivalTime = $startTime->addMinutes($arrivalTime);
            } else {
                $stoppageArrivalTime = $startTime;
            }

            $stoppages[] = [
                'name' => $routeStoppage->stoppage->name,
                'arrival_time' => \Cal::time($stoppageArrivalTime),
            ];

            if ($this->direction != Direction::DEPARTURE) {
                $stoppageArrivalTime = $startTime->addMinutes($arrivalTime);
            }
        }

        return $stoppages;
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->where('transport_routes.uuid', $uuid)
            ->getOrFail(trans('transport.route.route'));
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transport_route')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
