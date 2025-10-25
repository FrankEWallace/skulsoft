<?php

namespace App\Models\Inventory;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockItem extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'stock_items';

    protected $casts = [
        'meta' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StockCategory::class, 'stock_category_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function getIsQuantityEditableAttribute(): bool
    {
        return StockItemRecord::query()
            ->whereStockItemId($this->id)
            ->exists() ? false : true;
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $stockCategories = StockCategory::query()
            ->byTeam()
            ->filterAccessible()
            ->pluck('id')
            ->all();

        $query->whereIn('stock_category_id', $stockCategories);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('inventory.stock_item.stock_item'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('category', function ($q) use ($teamId) {
            $q->whereHas('inventory', function ($q) use ($teamId) {
                $q->byTeam($teamId);
            });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stock_item')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
