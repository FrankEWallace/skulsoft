<?php

namespace App\ValueObjects;

use Illuminate\Support\Arr;

class Percent
{
    public float $value;

    public string $formatted;

    public string $color;

    public string $inverseColor;

    public function __construct(mixed $value = 0)
    {
        $this->value = $this->getValue($value);

        $this->formatted = $this->getFormattedValue();

        $this->color = $this->getPercentageColor();

        $this->inverseColor = $this->getInversePercentageColor();
    }

    public static function from(mixed $value): self
    {
        if (! is_numeric($value)) {
            $value = 0;
        }

        return new self($value);
    }

    public function getValue(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            return (float) str_replace(',', '', $value);
        }

        if (is_array($value)) {
            return (float) Arr::get($value, 'value', 0);
        }

        return 0;
    }

    public function getFormattedValue(): string
    {
        return number_format($this->value, 2, '.', ',').'%';
    }

    public function getPercentageColor(): string
    {
        return match (true) {
            $this->value <= 20 => 'bg-danger',
            $this->value > 20 && $this->value <= 40 => 'bg-warning',
            $this->value > 40 && $this->value <= 80 => 'bg-info',
            $this->value > 80 => 'bg-success',
        };
    }

    public function getInversePercentageColor(): string
    {
        return match (true) {
            $this->value <= 10 => 'bg-success',
            $this->value > 10 && $this->value <= 50 => 'bg-info',
            $this->value > 50 && $this->value <= 80 => 'bg-warning',
            $this->value > 80 => 'bg-danger',
        };
    }
}
