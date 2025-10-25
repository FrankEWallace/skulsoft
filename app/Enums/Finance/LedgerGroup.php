<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;

enum LedgerGroup: string
{
    use HasEnum;

    case CASH = 'cash';
    case BANK_ACCOUNT = 'bank_account';
    case OVERDRAFT_ACCOUNT = 'overdraft_account';
    case SUNDRY_DEBTOR = 'sundry_debtor';
    case SUNDRY_CREDITOR = 'sundry_creditor';
    case DIRECT_INCOME = 'direct_income';
    case INDIRECT_INCOME = 'indirect_income';
    case DIRECT_EXPENSE = 'direct_expense';
    case INDIRECT_EXPENSE = 'indirect_expense';

    public static function translation(): string
    {
        return 'finance.ledger.groups.';
    }

    public static function primaryLedgers(): array
    {
        return [
            self::CASH->value,
            self::BANK_ACCOUNT->value,
            self::OVERDRAFT_ACCOUNT->value,
        ];
    }

    public static function secondaryLedgers(): array
    {
        return [
            self::SUNDRY_DEBTOR->value,
            self::SUNDRY_CREDITOR->value,
            self::DIRECT_EXPENSE->value,
            self::INDIRECT_EXPENSE->value,
            self::DIRECT_INCOME->value,
            self::INDIRECT_INCOME->value,
        ];
    }

    public static function vendors(): array
    {
        return [
            self::SUNDRY_DEBTOR->value,
            self::SUNDRY_CREDITOR->value,
        ];
    }

    public function isPrimaryLedger(): bool
    {
        return in_array($this->value, self::primaryLedgers());
    }

    public function isSecondaryLedger(): bool
    {
        return in_array($this->value, self::secondaryLedgers());
    }

    public function isVendor(): bool
    {
        return in_array($this->value, self::vendors());
    }

    public function hasCodeNumber(): bool
    {
        return match ($this) {
            self::CASH => true,
            self::BANK_ACCOUNT => true,
            self::OVERDRAFT_ACCOUNT => true,
            default => false
        };
    }

    public function hasContact(): bool
    {
        return match ($this) {
            self::SUNDRY_CREDITOR => true,
            self::SUNDRY_DEBTOR => true,
            default => false
        };
    }

    public function hasAccount(): bool
    {
        return match ($this) {
            self::BANK_ACCOUNT => true,
            self::OVERDRAFT_ACCOUNT => true,
            self::SUNDRY_CREDITOR => true,
            self::SUNDRY_DEBTOR => true,
            default => false
        };
    }
}
