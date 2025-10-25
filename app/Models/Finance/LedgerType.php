<?php

namespace App\Models\Finance;

use App\Casts\EnumCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasParent;
use App\Concerns\HasUuid;
use App\Enums\Finance\LedgerGroup;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LedgerType extends Model
{
    use HasFactory, HasFilter, HasMeta, HasParent, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'ledger_types';

    protected $casts = [
        'type' => EnumCast::class.':'.LedgerGroup::class,
        'is_default' => 'boolean',
        'meta' => 'array',
    ];

    protected $with = ['parent', 'nestedParents'];

    public function getHasAccountAttribute(): bool
    {
        if (is_null($this->parent_id)) {
            return $this->type?->hasAccount() ?? false;
        }

        $topAncestor = $this->topAncestor();

        return $topAncestor?->type?->hasAccount() ?? false;
    }

    public function getHasContactAttribute(): bool
    {
        if (is_null($this->parent_id)) {
            return $this->type?->hasContact() ?? false;
        }

        $topAncestor = $this->topAncestor();

        return $topAncestor?->type?->hasContact() ?? false;
    }

    public function getHasCodeNumberAttribute(): bool
    {
        if (is_null($this->parent_id)) {
            return $this->type?->hasCodeNumber() ?? false;
        }

        $topAncestor = $this->topAncestor();

        return $topAncestor?->type?->hasCodeNumber() ?? false;
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeByTeam(Builder $query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null, string $field = 'message')
    {
        return $query
            ->byTeam()
            ->whereUuid($uuid)
            ->getOrFail(trans('finance.ledger_type.ledger_type'), $field);
    }

    public function scopeFindByTypeOrFail(Builder $query, ?string $type = null, string $field = 'message')
    {
        return $query
            ->byTeam()
            ->whereType($type)
            ->getOrFail(trans('finance.ledger_type.ledger_type'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ledger_type')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
