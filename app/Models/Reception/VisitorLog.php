<?php

namespace App\Models\Reception;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Employee\Employee;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VisitorLog extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'visitor_logs';

    protected $casts = [
        'entry_at' => DateTimeCast::class,
        'exit_at' => DateTimeCast::class,
        'company' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'VisitorLog';
    }

    public function visitor()
    {
        return $this->morphTo();
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'purpose_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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
            ->getOrFail(trans('reception.visitor_log.visitor_log'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('visitor_log')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
