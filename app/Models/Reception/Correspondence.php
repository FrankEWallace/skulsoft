<?php

namespace App\Models\Reception;

use App\Casts\DateCast;
use App\Casts\EnumCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Reception\CorrespondenceMode;
use App\Enums\Reception\CorrespondenceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Correspondence extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'correspondences';

    protected $casts = [
        'type' => EnumCast::class.':'.CorrespondenceType::class,
        'mode' => EnumCast::class.':'.CorrespondenceMode::class,
        'date' => DateCast::class,
        'sender' => 'array',
        'receiver' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Correspondence';
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_id');
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
            ->getOrFail(trans('reception.correspondence.correspondence'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('correspondence')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
