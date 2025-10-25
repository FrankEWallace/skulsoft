<?php

namespace App\Models\Academic;

use App\Casts\DateCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Certificate extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'certificates';

    protected $attributes = [];

    protected $casts = [
        'date' => DateCast::class,
        'custom_fields' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function getIsDuplicateAttribute(): bool
    {
        return (bool) $this->getMeta('is_duplicate');
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->where('uuid', $uuid)
            ->getOrFail(trans('academic.certificate.certificate'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('template', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('certificate')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
