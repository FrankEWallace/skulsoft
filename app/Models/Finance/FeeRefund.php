<?php

namespace App\Models\Finance;

use App\Casts\DateCast;
use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeRefund extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'fee_refunds';

    protected $attributes = [];

    protected $casts = [
        'is_cancelled' => 'boolean',
        'date' => DateCast::class,
        'total' => PriceCast::class,
        'meta' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(FeeRefundRecord::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function scopeWithTransaction(Builder $query)
    {
        $query
            ->addSelect(['transaction_id' => TransactionRecord::select('transaction_id')
                ->whereColumn('model_id', 'fee_refunds.id')
                ->where('model_type', 'FeeRefund')
                ->limit(1),
            ]);
    }

    public function scopeByPeriod(Builder $query, ?int $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->whereHas('student', function ($q) use ($periodId) {
            $q->wherePeriodId($periodId);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fee_refund')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
