<?php

namespace App\Models\Finance;

use App\Casts\PriceCast;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TransactionRecord extends Model
{
    use HasFactory, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transaction_records';

    protected $casts = [
        'amount' => PriceCast::class,
        'meta' => 'array',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class, 'transaction_id');
    }

    public function getAdditionalFees($type = 'charge'): array
    {
        $field = $type == 'charge' ? 'additional_charges' : 'additional_discounts';

        return collect($this->getMeta($field) ?? [])->map(function ($fee) {
            return [
                'label' => Arr::get($fee, 'label'),
                'amount' => \Price::from(Arr::get($fee, 'amount', 0)),
            ];
        })->toArray();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transaction_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
