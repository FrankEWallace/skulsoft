<?php

namespace App\Models\Asset\Building;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Block extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'blocks';

    protected $casts = [
        'asset' => 'array',
        'meta' => 'array',
    ];

    protected $with = [];

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function scopeHostel(Builder $query)
    {
        $query->where('blocks.type', '=', 'hostel');
    }

    public function scopeNotAHostel(Builder $query)
    {
        $query->where(function ($q) {
            $q->where('blocks.type', '!=', 'hostel')
                ->orWhereNull('blocks.type');
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->notAHostel()
            ->where('uuid', $uuid)
            ->getOrFail(trans('asset.building.block.block'));
    }

    public function scopeByTeam(Builder $query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('block')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
