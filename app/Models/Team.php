<?php

namespace App\Models;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Domain\Academic\Models\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Team extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'teams';

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function periods(): HasMany
    {
        return $this->hasMany(Period::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeAllowedTeams(Builder $query)
    {
        if (\Auth::check()) {
            $query->when(! \Auth::user()->is_default, function ($q) {
                $q->whereIn('id', config('config.teams', []));
            });
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('team')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
