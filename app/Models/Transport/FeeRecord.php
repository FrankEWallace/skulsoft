<?php

namespace App\Models\Transport;

use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeRecord extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transport_fee_records';

    protected $attributes = [];

    protected $casts = [
        'arrival_amount' => PriceCast::class,
        'departure_amount' => PriceCast::class,
        'roundtrip_amount' => PriceCast::class,
        'meta' => 'array',
    ];

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'transport_fee_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class, 'transport_circle_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transport_fee')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
