<?php

namespace App\Models\Communication;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Communication\Type;
use App\Models\Academic\Period;
use App\Models\Audience;
use App\Models\User;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Communication extends Model
{
    use AudienceScope, HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'communications';

    protected $attributes = [];

    protected $casts = [
        'type' => Type::class,
        'recipients' => 'array',
        'lists' => 'array',
        'audience' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Communication';
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'shareable');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $query->where(function ($q) {
            $q->accessible();
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($uuid)
            ->getOrFail(trans('communication.communication'));
    }

    public function scopeFindEmailByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereType(Type::EMAIL)
            ->whereUuid($uuid)
            ->getOrFail(trans('communication.email.email'));
    }

    public function scopeFindSMSByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->whereType(Type::SMS)
            ->whereUuid($uuid)
            ->getOrFail(trans('communication.sms.sms'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('period', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('communication')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
