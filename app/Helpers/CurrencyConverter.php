<?php

namespace App\Helpers;

use App\ValueObjects\Currency;
use Illuminate\Support\Arr;

class CurrencyConverter
{
    public static function toWord(float $amount, ?string $currencyName = null)
    {
        if (! $currencyName) {
            $currencyName = config('config.system.currency');
        }

        $currency = Currency::from($currencyName)->getCurrencyDetail();

        if ($currencyName == 'INR') {
            return self::toIndianFormat($amount, $currency);
        }

        return self::toGlobalFormat($amount, $currency);
    }

    public static function toIndianFormat(float $amount, array $currency)
    {
        if ($amount < 0) {
            $amount = abs($amount);
        }

        $unitName = Arr::get($currency, 'unit_name', 'Rupees');
        $subUnitName = Arr::get($currency, 'sub_unit_name', 'Paise');

        $words = '';

        $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $teens = ['', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', 'Ten', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $unitPart = (int) floor($amount);
        $subUnitPart = (int) round(($amount - $unitPart) * 100);

        // Handle crores (10000000)
        $crores = (int) ($unitPart / 10000000);
        $remainder = $unitPart % 10000000;

        // Handle lacs (100000)
        $lacs = (int) ($remainder / 100000);
        $remainder = $remainder % 100000;

        // Handle thousands
        $thousands = (int) ($remainder / 1000);
        $remainder = $remainder % 1000;

        // Handle hundreds
        $hundreds = (int) ($remainder / 100);
        $remainder = $remainder % 100;

        // Handle remaining tens and ones
        $tens_ones = $remainder;

        $words = '';

        // Convert crores (handling thousands and hundreds of crores correctly)
        if ($crores > 0) {
            // Handle thousands
            $crore_thousands = (int) ($crores / 1000);
            $remaining_crores = $crores % 1000;

            if ($crore_thousands > 0) {
                $words .= self::convertTwoDigits($crore_thousands, $units, $teens, $tens).' Thousand ';
            }

            // Handle hundreds
            $crore_hundreds = (int) ($remaining_crores / 100);
            $final_crores = $remaining_crores % 100;

            if ($crore_hundreds > 0) {
                $words .= $units[$crore_hundreds].' Hundred ';
                if ($final_crores > 0) {
                    $words .= 'and ';
                }
            }

            // Handle remaining crores
            if ($final_crores > 0) {
                $words .= self::convertTwoDigits($final_crores, $units, $teens, $tens).' ';
            }

            $words .= 'Crore ';
        }

        // Convert lacs
        if ($lacs > 0) {
            $words .= self::convertTwoDigits($lacs, $units, $teens, $tens).' Lac ';
        }

        // Convert thousands
        if ($thousands > 0) {
            $words .= self::convertTwoDigits($thousands, $units, $teens, $tens).' Thousand ';
        }

        // Convert hundreds
        if ($hundreds > 0) {
            $words .= $units[$hundreds].' Hundred ';
        }

        // Convert tens and ones
        if ($tens_ones > 0) {
            if ($words != '') {
                $words .= 'and ';
            }
            $words .= self::convertTwoDigits($tens_ones, $units, $teens, $tens);
        }

        // Handle subunit (paise)
        $finalWords = trim($words).' '.$unitName;
        if ($subUnitPart > 0) {
            $finalWords .= ' and '.self::convertTwoDigits($subUnitPart, $units, $teens, $tens).' '.$subUnitName;
        }

        return $finalWords;
    }

    private static function convertTwoDigits($number, $units, $teens, $tens)
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 10) {
            return $units[$number];
        }

        if ($number == 10) {
            return $tens[1];
        }

        if ($number > 10 && $number < 20) {
            return $teens[$number - 10];
        }

        // Handle numbers greater than 99 by breaking them down
        if ($number >= 100) {
            $number = $number % 100;
            if ($number == 0) {
                return '';
            }
        }

        $ten = (int) ($number / 10);
        $one = $number % 10;

        return $tens[$ten].($one > 0 ? ' '.$units[$one] : '');
    }

    public static function toGlobalFormat(float $amount, array $currency)
    {
        if ($amount < 0) {
            $amount = abs($amount);
        }

        $unitName = Arr::get($currency, 'unit_name', 'Rupees');
        $subUnitName = Arr::get($currency, 'sub_unit_name', 'Cent');

        $words = '';

        $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $teens = ['Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', 'Ten', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $thousands = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

        $unitPart = (int) floor($amount);
        $subUnitPart = (int) round(($amount - $unitPart) * 100);

        if ($unitPart == 0) {
            $words = 'Zero';
        } else {
            $unitPartArray = array_reverse(str_split($unitPart));
            $chunks = array_chunk($unitPartArray, 3);

            foreach ($chunks as $index => $chunk) {
                if (count($chunk) > 0) {
                    $chunkWords = '';

                    // Hundreds
                    if (isset($chunk[2]) && $chunk[2] > 0) {
                        $chunkWords .= $units[$chunk[2]].' Hundred ';
                    }

                    // Tens and Ones
                    $tensAndOnes = ($chunk[1] ?? 0) * 10 + ($chunk[0] ?? 0);
                    if ($tensAndOnes > 0) {
                        if (! empty($chunkWords)) {
                            $chunkWords .= 'and ';
                        }

                        if ($tensAndOnes > 10 && $tensAndOnes < 20) {
                            $chunkWords .= $teens[$tensAndOnes - 11].' ';
                        } else {
                            if (isset($chunk[1]) && $chunk[1] > 0) {
                                $chunkWords .= $tens[$chunk[1]].' ';
                            }
                            if (isset($chunk[0]) && $chunk[0] > 0) {
                                $chunkWords .= $units[$chunk[0]].' ';
                            }
                        }
                    }

                    if (! empty($chunkWords)) {
                        $words = $chunkWords.($index > 0 ? $thousands[$index].' ' : '').$words;
                    }
                }
            }
        }

        $subUnitPartWords = '';
        if ($subUnitPart > 0) {
            $subUnitPartWords = ' and ';
            if ($subUnitPart > 10 && $subUnitPart < 20) {
                $subUnitPartWords .= $teens[$subUnitPart - 11];
            } else {
                $ten = floor($subUnitPart / 10);
                $one = $subUnitPart % 10;
                if ($ten > 0) {
                    $subUnitPartWords .= $tens[$ten];
                    if ($one > 0) {
                        $subUnitPartWords .= ' '.$units[$one];
                    }
                } else {
                    $subUnitPartWords .= $units[$one];
                }
            }
            $subUnitPartWords .= ' '.$subUnitName;
        }

        return ucfirst(trim($words)).' '.$unitName.$subUnitPartWords;
    }
}
