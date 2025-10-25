<?php

namespace App\Models\Transport;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RouteStoppage extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transport_route_stoppages';

    protected $attributes = [];

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function stoppage(): BelongsTo
    {
        return $this->belongsTo(Stoppage::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(RoutePassenger::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transport_route_stoppage')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
