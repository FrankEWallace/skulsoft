<?php

namespace App\Models\Inventory;

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

class StockTransfer extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'stock_transfers';

    protected $casts = [
        'date' => DateCast::class,
        'return_due_date' => DateCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'StockTransfer';
    }

    public function from()
    {
        return $this->morphTo();
    }

    public function to()
    {
        return $this->morphTo();
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function items()
    {
        return $this->morphMany(StockItemRecord::class, 'model');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $inventories = Inventory::query()
            ->filterAccessible()
            ->pluck('id')
            ->all();

        $query->whereIn('inventory_id', $inventories);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('inventory.stock_transfer.stock_transfer'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('inventory', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stock_transfer')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
