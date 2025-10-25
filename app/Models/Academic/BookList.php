<?php

namespace App\Models\Academic;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Academic\BookListType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookList extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'book_lists';

    protected $attributes = [];

    protected $casts = [
        'type' => BookListType::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()?->current_period_id;

        $query->where(function ($q) {
            $q->whereHas('course', function ($q) {
                $q->whereHas('division', function ($q) {
                    $q->where('period_id', auth()->user()?->current_period_id);
                });
            });
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, $uuid)
    {
        return $query
            ->byPeriod()
            ->whereUuid($uuid)
            ->getOrFail(trans('academic.book_list.book_list'), 'message');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('book_list')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
