<?php

namespace App\Models\Inventory;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockCategory extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'stock_categories';

    protected $casts = [
        'meta' => 'array',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $inventories = Inventory::query()
            ->byTeam()
            ->filterAccessible()
            ->pluck('id')
            ->all();

        $query->whereIn('inventory_id', $inventories);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('inventory.stock_category.stock_category'));
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
            ->useLogName('stock_category')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
