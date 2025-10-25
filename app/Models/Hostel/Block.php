<?php

namespace App\Models\Hostel;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Incharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
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

    public function scopeWithCurrentIncharges(Builder $query)
    {
        $query->with([
            'incharges' => function ($q) {
                return $q->where('start_date', '<=', today()->toDateString())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', today()->toDateString());
                    });
            }, 'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithLastIncharge(Builder $query)
    {
        $query->addSelect(['incharge_id' => Incharge::select('id')
            ->whereColumn('model_id', 'blocks.id')
            ->where('model_type', 'HostelBlock')
            ->where('effective_date', '<=', today()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->hostel()
            ->where('uuid', $uuid)
            ->getOrFail(trans('hostel.block.block'));
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
