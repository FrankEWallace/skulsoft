<?php

namespace App\Models\Hostel;

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

class Floor extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'floors';

    protected $casts = [
        'asset' => 'array',
        'meta' => 'array',
    ];

    protected $with = ['block'];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function scopeWithBlock(Builder $query)
    {
        $query->select('floors.*', 'blocks.name as block_name', 'blocks.uuid as block_uuid')
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
            ->withBlock()
            ->hostel()
            ->where('floors.uuid', $uuid)
            ->getOrFail(trans('hostel.floor.floor'));
    }

    public function scopeByTeam(Builder $query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('block', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('floor')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
