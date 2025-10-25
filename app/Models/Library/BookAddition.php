<?php

namespace App\Models\Library;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookAddition extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'book_additions';

    protected $casts = [
        'date' => DateCast::class,
        'meta' => 'array',
    ];

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class, 'book_addition_id');
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->whereUuid($uuid)
            ->getOrFail(trans('library.book_addition.book_addition'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('book_addition')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
