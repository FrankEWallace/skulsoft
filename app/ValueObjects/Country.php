<?php

namespace App\ValueObjects;

use Illuminate\Support\Arr;

class Country
{
    public ?string $name;

    public ?string $code;

    public ?string $altCode;

    public function __construct(?string $value = '')
    {
        $country = $this->getValue($value);

        $this->name = Arr::get($country, 'name');
        $this->code = Arr::get($country, 'code');
        $this->altCode = Arr::get($country, 'alt_code');
    }

    public static function from(?string $value = ''): self
    {
        return new self($value);
    }

    public function getValue(string $value): array
    {
        $countries = Arr::getVar('countries');

        return collect($countries)->firstWhere('code', $value) ?? [];
    }
}
