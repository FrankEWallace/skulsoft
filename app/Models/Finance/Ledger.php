<?php

namespace App\Models\Finance;

use App\Casts\PriceCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Finance\LedgerGroup;
use App\Enums\Finance\TransactionType;
use App\ValueObjects\Price;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ledger extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'ledgers';

    protected $casts = [
        'opening_balance' => PriceCast::class,
        'current_balance' => PriceCast::class,
        'account' => 'array',
        'address' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    protected $appends = ['code'];

    protected $with = ['type'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(LedgerType::class, 'ledger_type_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transactionRecords(): HasMany
    {
        return $this->hasMany(TransactionRecord::class);
    }

    // public function accounts()
    // {
    //     return $this->morphMany(Account::class, 'accountable');
    // }

    public function getCodeAttribute(): ?string
    {
        return $this->getConfig('code');
    }

    public function getNetBalanceAttribute(): Price
    {
        return \Price::from($this->current_balance->value + $this->opening_balance->value);
    }

    public function scopeByTeam(Builder $query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('type', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeSubType(Builder $query, ?string $subType = null)
    {
        $query->when($subType, function ($q, $subType) {
            $q->whereHas('type', function ($q) use ($subType) {
                if ($subType == 'primary') {
                    $q->whereIn('type', LedgerGroup::primaryLedgers());
                } elseif ($subType == 'secondary') {
                    $q->whereIn('type', LedgerGroup::secondaryLedgers());
                } elseif ($subType == 'vendor') {
                    $q->whereIn('type', LedgerGroup::vendors());
                }
            });
        });
    }

    public function updatePrimaryBalance(TransactionType $transactionType, float $amount = 0)
    {
        $this->current_balance = $this->current_balance->value + $this->primaryMultiplier($transactionType) * $amount;
        $this->save();
    }

    public function reversePrimaryBalance(TransactionType $transactionType, float $amount = 0)
    {
        $this->current_balance = $this->current_balance->value - $this->primaryMultiplier($transactionType) * $amount;
        $this->save();
    }

    public function updateSecondaryBalance(TransactionType $transactionType, float $amount = 0)
    {
        $this->current_balance = $this->current_balance->value + $this->secondaryMultiplier($transactionType) * $amount;
        $this->save();
    }

    public function reverseSecondaryBalance(TransactionType $transactionType, float $amount = 0)
    {
        $this->current_balance = $this->current_balance->value - $this->secondaryMultiplier($transactionType) * $amount;
        $this->save();
    }

    public function primaryMultiplier(TransactionType $transactionType): int
    {
        if ($transactionType == TransactionType::PAYMENT && in_array($this->type->type, [LedgerGroup::CASH, LedgerGroup::BANK_ACCOUNT])) {
            return -1;
        }

        if ($transactionType == TransactionType::RECEIPT && in_array($this->type->type, [LedgerGroup::OVERDRAFT_ACCOUNT])) {
            return -1;
        }

        if ($transactionType == TransactionType::TRANSFER && ! in_array($this->type->type, [LedgerGroup::OVERDRAFT_ACCOUNT])) {
            return -1;
        }

        return 1;
    }

    public function secondaryMultiplier(TransactionType $transactionType): int
    {
        if ($transactionType == TransactionType::PAYMENT && in_array($this->type->type, [LedgerGroup::SUNDRY_CREDITOR, LedgerGroup::DIRECT_EXPENSE, LedgerGroup::INDIRECT_EXPENSE])) {
            return -1;
        }

        if ($transactionType == TransactionType::RECEIPT && in_array($this->type->type, [LedgerGroup::SUNDRY_DEBTOR, LedgerGroup::DIRECT_INCOME, LedgerGroup::INDIRECT_INCOME])) {
            return -1;
        }

        if ($transactionType == TransactionType::TRANSFER && in_array($this->type->type, [LedgerGroup::OVERDRAFT_ACCOUNT])) {
            return -1;
        }

        return 1;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ledger')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
