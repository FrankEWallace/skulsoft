<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum FamilyRelation: string
{
    use HasEnum;

    case FATHER = 'father';
    case MOTHER = 'mother';
    case SPOUSE = 'spouse';
    case SIBLING = 'sibling';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'list.family_relations.';
    }
}
