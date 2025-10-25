<?php

namespace App\Models\Transport;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RoutePassenger extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transport_route_passengers';

    protected $attributes = [];

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function stoppage(): BelongsTo
    {
        return $this->belongsTo(Stoppage::class, 'stoppage_id');
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transport_route_passenger')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
