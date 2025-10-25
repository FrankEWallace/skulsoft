<?php

namespace App\ValueObjects;

use Illuminate\Support\Arr;

class Price
{
    public float $value;

    public string $currency;

    public array $currencyDetail;

    public string $formatted;

    public function __construct(mixed $amount = 0, ?string $currency = null)
    {
        $this->currency = $currency ?? config('config.system.currency', 'USD');

        $this->currencyDetail = $this->getCurrencyDetail();

        $this->value = $this->getValue($amount);

        $this->formatted = $this->getFormattedAmount();
    }

    public static function from(mixed $amount, ?string $currency = null): self
    {
        if (! is_numeric($amount)) {
            $amount = 0;
        }

        return new self($amount, $currency);
    }

    public function getCurrencyDetail()
    {
        $currencies = collect(Arr::getVar('currencies'));

        $detail = $currencies->firstWhere('name', $this->currency);

        if (! $detail) {
            $detail = $currencies->firstWhere('name', config('config.system.currency'));
        }

        return $detail;
    }

    public function getValue(float $amount): float
    {
        return round($amount, Arr::get($this->currencyDetail, 'decimal', 2));
    }

    public function getFormattedAmount(): string
    {
        $amountValue = $this->value;

        $amount = $this->getNumberFormat();

        if (Arr::get($this->currencyDetail, 'position') === 'prefix') {
            if ($amountValue < 0) {
                return '-'.Arr::get($this->currencyDetail, 'symbol').''.$amount;
            }

            return Arr::get($this->currencyDetail, 'symbol').''.$amount;
        }

        return $amount.''.Arr::get($this->currencyDetail, 'symbol');
    }

    private function getNumberFormat(): string
    {
        if ($this->currency === 'INR') {
            return $this->indianMoneyFormat(abs($this->value));
        }

        $decimal = Arr::get($this->currencyDetail, 'decimal', 2);
        $decimalDelimiter = Arr::get($this->currencyDetail, 'decimal_delimiter', '.');

        return number_format(abs($this->value), $decimal, $decimalDelimiter, ',');
    }

    private function indianMoneyFormat($number)
    {
        $decimal = (string) ($number - floor($number));
        $money = floor($number);
        $length = strlen($money);
        $delimiter = '';
        $money = strrev($money);

        for ($i = 0; $i < $length; $i++) {
            if (($i == 3 || ($i > 3 && ($i - 1) % 2 == 0)) && $i != $length) {
                $delimiter .= ',';
            }
            $delimiter .= $money[$i];
        }

        $result = strrev($delimiter);
        $decimal = preg_replace("/0\./i", '.', $decimal);
        $decimal = substr($decimal, 0, 3);

        if ($decimal != '0') {
            $result = $result.$decimal;
        }

        return $result;
    }
}
