<?php

namespace App\ValueObjects;

use Illuminate\Support\Arr;

class Currency
{
    public string $value;

    public string $name;

    public string $symbol;

    public string $position;

    public string $decimal;

    public string $thousand;

    public string $decimalDelimeter;

    public string $thousandDelimeter;

    public function __construct(mixed $value = null)
    {
        if (! is_string($value)) {
            $value = null;
        }

        $this->value = $value ?? config('config.system.currency');

        $detail = $this->getCurrencyDetail();

        $this->name = Arr::get($detail, 'name');

        $this->symbol = Arr::get($detail, 'symbol');

        $this->position = Arr::get($detail, 'position');

        $this->decimal = Arr::get($detail, 'decimal');

        $this->decimalDelimeter = Arr::get($detail, 'decimal_delimeter');

        $this->thousandDelimeter = Arr::get($detail, 'thousand_delimeter');
    }

    public static function from(mixed $value): self
    {
        return new self($value);
    }

    public function getCurrencyDetail()
    {
        $currencies = collect(Arr::getVar('currencies'));

        return $currencies->firstWhere('name', $this->value) ?? [];
    }
}
