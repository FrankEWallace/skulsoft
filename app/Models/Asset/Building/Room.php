<?php

namespace App\Models\Asset\Building;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Room extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'rooms';

    protected $casts = [
        'asset' => 'array',
        'meta' => 'array',
    ];

    protected $with = [];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function getFullNameAttribute()
    {
        return $this->name.' '.$this->block_name.' '.$this->floor_name;
    }

    public function scopeWithFloorAndBlock(Builder $query)
    {
        $query->select('rooms.*', 'floors.name as floor_name', 'floors.uuid as floor_uuid', 'blocks.name as block_name', 'blocks.uuid as block_uuid')
            ->join('floors', 'floors.id', '=', 'rooms.floor_id')
            ->join('blocks', 'blocks.id', '=', 'floors.block_id')
            ->where('blocks.team_id', auth()->user()?->current_team_id);
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
            ->withFloorAndBlock()
            ->notAHostel()
            ->where('rooms.uuid', $uuid)
            ->getOrFail(trans('asset.building.room.room'));
    }

    public function scopeByTeam(Builder $query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('floor', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('room')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
