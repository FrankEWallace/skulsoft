<?php

namespace App\Models\Library;

use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Book extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'books';

    protected $casts = [
        'price' => PriceCast::class,
        'meta' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'author_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'publisher_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'language_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'topic_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'category_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies()
    {
        return $this->hasMany(BookCopy::class, 'book_id')
            ->whereNull('book_copies.hold_status')
            ->whereDoesntHave('latestTransactionRecord', function ($query) {
                $query->whereNull('return_date');
            });
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('book')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
