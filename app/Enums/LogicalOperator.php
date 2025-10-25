<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum LogicalOperator: string
{
    use HasEnum;

    case AND = 'AND';
    case OR = 'OR';

    public static function translation(): string
    {
        return 'list.logical_operators.';
    }
}
