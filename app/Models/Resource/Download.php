<?php

namespace App\Models\Resource;

use App\Casts\DateTimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Audience;
use App\Models\Employee\Employee;
use App\Models\Team;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Download extends Model
{
    use AudienceScope, HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'downloads';

    protected $attributes = [];

    protected $casts = [
        'published_at' => DateTimeCast::class,
        'expires_at' => DateTimeCast::class,
        'audience' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Download';
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'shareable');
    }

    public function scopeWithUserId(Builder $query)
    {
        $query
            ->select('downloads.*', 'contacts.user_id')
            ->leftJoin('employees', 'employees.id', '=', 'downloads.employee_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'employees.contact_id');
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $query->where(function ($q) {
            $q->accessible();
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->withUserId()
            ->filterAccessible()
            ->where('downloads.uuid', $uuid)
            ->getOrFail(trans('resource.download.download'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->where('downloads.team_id', $teamId);
    }

    public function getIsEditableAttribute(): bool
    {
        if (! auth()->user()->can('download:edit')) {
            return false;
        }

        if (auth()->user()->hasRole('staff') && $this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function getIsDeletableAttribute(): bool
    {
        if (! auth()->user()->can('download:delete')) {
            return false;
        }

        if (auth()->user()->hasRole('staff') && $this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('download')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
