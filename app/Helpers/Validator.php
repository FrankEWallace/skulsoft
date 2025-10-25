<?php

namespace App\Helpers;

class Validator
{
    /**
     * Validate digits between
     */
    public static function digitsBetween($value = null, $minDigit = 1, $maxDigit = 1): bool
    {
        if (! $value) {
            return false;
        }

        $length = strlen($value);

        return preg_match('/[^0-9]/', $value) || $length < $minDigit || $length > $maxDigit;
    }

    /**
     * Validate number
     */
    public static function number($value = null, ?int $min = null, ?int $max = null, $type = 'integer'): bool
    {
        if (! $value) {
            return false;
        }

        if (! is_numeric($value)) {
            return true;
        }

        if ($type == 'integer' && round($value) != $value) {
            return true;
        }

        if ($type == 'amount' && $value < 0) {
            return true;
        }

        if ($min && $value < $min) {
            return true;
        }

        if ($max && $value > $max) {
            return true;
        }

        return false;
    }
}
