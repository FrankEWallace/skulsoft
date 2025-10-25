<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum ComparisonOperator: string
{
    use HasEnum;

    case EQUAL = 'equal';
    case NOT_EQUAL = 'not_equal';
    case LESS_THAN = 'less_than';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case GREATER_THAN = 'greater_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';

    public static function translation(): string
    {
        return 'list.comparison_operators.';
    }

    public function operator(): string
    {
        return match ($this) {
            self::EQUAL => '=',
            self::NOT_EQUAL => '!=',
            self::LESS_THAN => '<',
            self::LESS_THAN_OR_EQUAL => '<=',
            self::GREATER_THAN => '>',
            self::GREATER_THAN_OR_EQUAL => '>=',
        };
    }

    public static function withOperator($value): array
    {
        if (empty($value)) {
            return [];
        }

        $operator = self::tryFrom($value);

        return [
            'label' => trans(self::translation().$operator->value),
            'value' => $operator->value,
            'operator' => $operator->operator(),
        ];
    }
}
