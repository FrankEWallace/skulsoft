<?php

namespace App\Support;

use App\Helpers\CalHelper;
use Illuminate\Support\Arr;

class NumberToWordConverter
{
    public static function dateToWord($date)
    {
        if (! CalHelper::validateDate($date)) {
            return '-';
        }

        $dates = [
            1 => 'First',
            2 => 'Second',
            3 => 'Third',
            4 => 'Fourth',
            5 => 'Fifth',
            6 => 'Sixth',
            7 => 'Seventh',
            8 => 'Eighth',
            9 => 'Ninth',
            10 => 'Tenth',
            11 => 'Eleventh',
            12 => 'Twelfth',
            13 => 'Thirteenth',
            14 => 'Fourteenth',
            15 => 'Fifteenth',
            16 => 'Sixteenth',
            17 => 'Seventeenth',
            18 => 'Eighteenth',
            19 => 'Nineteenth',
            20 => 'Twentieth',
            21 => 'Twenty First',
            22 => 'Twenty Second',
            23 => 'Twenty Third',
            24 => 'Twenty Fourth',
            25 => 'Twenty Fifth',
            26 => 'Twenty Sixth',
            27 => 'Twenty Seventh',
            28 => 'Twenty Eighth',
            29 => 'Twenty Ninth',
            30 => 'Thirtieth',
            31 => 'Thirty First',
        ];

        $day = date('j', strtotime($date));
        $month = date('F', strtotime($date));
        $year = date('Y', strtotime($date));

        return ucwords(
            Arr::get($dates, $day).' '.$month.' '.self::numberToWord($year)
        );
    }

    public static function numberToWord($num = false)
    {
        $num = str_replace([',', ' '], '', trim($num));
        if (! $num) {
            return false;
        }
        $num = abs($num);
        $num = (int) $num;
        $words = [];
        $list1 = [
            '',
            'one',
            'two',
            'three',
            'four',
            'five',
            'six',
            'seven',
            'eight',
            'nine',
            'ten',
            'eleven',
            'twelve',
            'thirteen',
            'fourteen',
            'fifteen',
            'sixteen',
            'seventeen',
            'eighteen',
            'nineteen',
        ];
        $list2 = [
            '',
            'ten',
            'twenty',
            'thirty',
            'forty',
            'fifty',
            'sixty',
            'seventy',
            'eighty',
            'ninety',
            'hundred',
        ];
        $list3 = [
            '',
            'thousand',
            'million',
            'billion',
            'trillion',
            'quadrillion',
            'quintillion',
            'sextillion',
            'septillion',
            'octillion',
            'nonillion',
            'decillion',
            'undecillion',
            'duodecillion',
            'tredecillion',
            'quattuordecillion',
            'quindecillion',
            'sexdecillion',
            'septendecillion',
            'octodecillion',
            'novemdecillion',
            'vigintillion',
        ];
        $num_length = strlen($num);
        $levels = (int) (($num_length + 2) / 3);
        $max_length = $levels * 3;
        $num = substr('00'.$num, -$max_length);
        $num_levels = str_split($num, 3);
        for ($i = 0; $i < count($num_levels); $i++) {
            $levels--;
            $hundreds = (int) ($num_levels[$i] / 100);
            $hundreds = $hundreds ? ' '.$list1[$hundreds].' hundred'.' ' : '';
            $tens = (int) ($num_levels[$i] % 100);
            $singles = '';
            if ($tens < 20) {
                $tens = $tens ? ' '.$list1[$tens].' ' : '';
            } else {
                $tens = (int) ($tens / 10);
                $tens = ' '.$list2[$tens].' ';
                $singles = (int) ($num_levels[$i] % 10);
                $singles = ' '.$list1[$singles].' ';
            }
            $words[] =
              $hundreds.
              $tens.
              $singles.
              ($levels && (int) $num_levels[$i] ? ' '.$list3[$levels].' ' : '');
        }
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }

        return ucwords(implode(' ', $words));
    }
}
