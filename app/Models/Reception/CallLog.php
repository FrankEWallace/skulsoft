<?php

namespace App\Models\Reception;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Reception\CallType;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CallLog extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'call_logs';

    protected $casts = [
        'type' => CallType::class,
        'call_at' => DateTimeCast::class,
        'company' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'CallLog';
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'purpose_id');
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
            ->getOrFail(trans('reception.call_log.call_log'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('call_log')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
