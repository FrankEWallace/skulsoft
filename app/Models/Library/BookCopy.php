<?php

namespace App\Models\Library;

use App\Casts\DateCast;
use App\Casts\EnumCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Library\HoldStatus;
use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookCopy extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'book_copies';

    protected $casts = [
        'hold_status' => EnumCast::class.':'.HoldStatus::class,
        'invoice_date' => DateCast::class,
        'meta' => 'array',
    ];

    public function addition(): BelongsTo
    {
        return $this->belongsTo(BookAddition::class, 'book_addition_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'condition_id');
    }

    public function getLocationAttribute(): string
    {
        $location = [];

        if ($this->room_number) {
            $location[] = $this->room_number;
        }

        if ($this->rack_number) {
            $location[] = $this->rack_number;
        }

        if ($this->shelf_number) {
            $location[] = $this->shelf_number;
        }

        return implode(' / ', $location);
    }

    public function latestTransactionRecord()
    {
        return $this->hasOne(TransactionRecord::class, 'book_copy_id')
            ->latest('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('book_copy')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
