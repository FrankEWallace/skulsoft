<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum BloodGroup: string
{
    use HasEnum;

    case O_POSITIVE = 'o_positive';
    case O_NEGATIVE = 'o_negative';
    case A_POSITIVE = 'a_positive';
    case A_NEGATIVE = 'a_negative';
    case B_POSITIVE = 'b_positive';
    case B_NEGATIVE = 'b_negative';
    case AB_POSITIVE = 'ab_positive';
    case AB_NEGATIVE = 'ab_negative';

    public static function translation(): string
    {
        return 'list.blood_groups.';
    }

    public static function aliases()
    {
        return [
            'o_positive' => [
                'o+ve', 'o +ve', 'o+', 'o positive', 'o +',
            ],
            'o_negative' => [
                'o-ve', 'o -ve', 'o-', 'o negative', 'o -',
            ],
            'a_positive' => [
                'a+ve', 'a +ve', 'a+', 'a positive', 'a +',
            ],
            'a_negative' => [
                'a-ve', 'a -ve', 'a-', 'a negative', 'a -',
            ],
            'b_positive' => [
                'b+ve', 'b +ve', 'b+', 'b positive', 'b +',
            ],
            'b_negative' => [
                'b-ve', 'b -ve', 'b-', 'b negative', 'b -',
            ],
            'ab_positive' => [
                'ab+ve', 'ab +ve', 'ab+', 'ab positive', 'ab +',
            ],
            'ab_negative' => [
                'ab-ve', 'ab -ve', 'ab-', 'ab negative', 'ab -',
            ],
        ];
    }

    public static function getAlias(string $alias)
    {
        $aliases = self::aliases();

        return $aliases[$alias] ?? [];
    }

    public static function tryFromAliases(?string $alias = null): ?BloodGroup
    {
        if (empty($alias)) {
            return null;
        }

        $alias = strtolower($alias);

        $bloodGroup = self::tryFrom($alias);

        if ($bloodGroup) {
            return $bloodGroup;
        }

        $aliases = self::aliases($alias);

        foreach ($aliases as $key => $values) {
            if (in_array($alias, $values)) {
                return self::tryFrom($key);
            }
        }

        return null;
    }
}
