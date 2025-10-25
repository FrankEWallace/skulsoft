<?php

namespace App\Models\Reception;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Reception\GatePassStatus;
use App\Enums\Reception\GatePassTo;
use App\Models\Audience;
use App\Models\Option;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GatePass extends Model
{
    use AudienceScope, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'gate_passes';

    protected $casts = [
        'start_at' => DateTimeCast::class,
        'end_at' => DateTimeCast::class,
        'left_at' => DateTimeCast::class,
        'returned_at' => DateTimeCast::class,
        'status' => GatePassStatus::class,
        'requester_type' => GatePassTo::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'GatePass';
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'purpose_id');
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'shareable');
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->byTeam()
            ->where('uuid', $uuid)
            ->getOrFail(trans('reception.gate_pass.gate_pass'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gate_pass')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
