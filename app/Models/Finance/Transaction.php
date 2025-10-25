<?php

namespace App\Models\Finance;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Finance\TransactionType;
use App\Models\Academic\Period;
use App\Models\Option;
use App\Models\Student\FeePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'transactions';

    protected $casts = [
        'type' => TransactionType::class,
        'date' => DateCast::class,
        'amount' => PriceCast::class,
        'reconciliation_date' => DateCast::class,
        'is_online' => 'boolean',
        'processed_at' => DateTimeCast::class,
        'handling_fee' => PriceCast::class,
        'cancelled_at' => DateTimeCast::class,
        'rejected_at' => DateTimeCast::class,
        'rejection_record' => 'array',
        'payment_gateway' => 'array',
        'failed_logs' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Transaction';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'category_id');
    }

    public function transactionable()
    {
        return $this->morphTo();
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(TransactionPayment::class, 'payment_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class, 'transaction_id');
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'transaction_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TransactionRecord::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(TransactionRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithRecord(Builder $query)
    {
        $query->addSelect(['record_id' => TransactionRecord::select('id')
            ->whereColumn('transaction_id', 'transactions.id')
            ->orderBy('id', 'asc')
            ->limit(1),
        ])->with('record.ledger');
    }

    public function scopeWithPayment(Builder $query)
    {
        $query->addSelect(['payment_id' => TransactionPayment::select('id')
            ->whereColumn('transaction_id', 'transactions.id')
            ->orderBy('id', 'asc')
            ->limit(1),
        ])->with('payment.method', 'payment.ledger');
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    private function getPaymentMethodDetail(TransactionPayment $payment): array
    {
        return [
            'name' => $payment->method->name,
            'has_instrument_number' => $payment->method->getConfig('has_instrument_number'),
            'has_instrument_date' => $payment->method->getConfig('has_instrument_date'),
            'has_clearing_date' => $payment->method->getConfig('has_clearing_date'),
            'has_bank_detail' => $payment->method->getConfig('has_bank_detail'),
            'has_reference_number' => $payment->method->getConfig('has_reference_number'),
            'instrument_number' => Arr::get($payment->details, 'instrument_number'),
            'instrument_date' => \Cal::date(Arr::get($payment->details, 'instrument_date')),
            'clearing_date' => \Cal::date(Arr::get($payment->details, 'clearing_date')),
            'bank_detail' => Arr::get($payment->details, 'bank_detail'),
            'reference_number' => Arr::get($payment->details, 'reference_number'),
        ];
    }

    public function getPaymentMethod(): array
    {
        if (! $this->relationLoaded('payment')) {
            return [];
        }

        return [
            'uuid' => $this->payment->method->uuid,
            'payment_uuid' => $this->payment->uuid,
            'amount' => $this->payment->amount,
            ...$this->getPaymentMethodDetail($this->payment),
        ];
    }

    public function getPaymentMethods(): array
    {
        if (! $this->relationLoaded('payments')) {
            return [];
        }

        return $this->payments->map(function ($payment) {
            return [
                'uuid' => $payment->method->uuid,
                'payment_uuid' => $payment->uuid,
                'amount' => $payment->amount,
                ...$this->getPaymentMethodDetail($payment),
            ];
        })->toArray();
    }

    public function isEditable(): bool
    {
        if ($this->is_online) {
            return false;
        }

        if ($this->transactionable_type) {
            return false;
        }

        return true;
    }

    public function isFeeReceiptEditable()
    {
        if ($this->cancelled_at->value || $this->rejected_at->value) {
            return false;
        }

        if ($this->is_online) {
            return false;
        }

        if (auth()->user()->is_default || auth()->user()->hasRole('admin')) {
            return true;
        }

        if ($this->user_id != auth()->id()) {
            return false;
        }

        return true;
    }

    public function getCanEditAttribute()
    {
        if ($this->head == 'student_fee') {
            return $this->isFeeReceiptEditable();
        } elseif ($this->head == 'registration_fee') {
            return false;
        }

        return $this->isEditable();
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereUuid($uuid)
            ->getOrFail(trans('finance.transaction.transaction'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('transaction')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
