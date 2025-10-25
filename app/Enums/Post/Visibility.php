<?php

namespace App\Enums\Post;

use App\Concerns\HasEnum;

enum Visibility: string
{
    use HasEnum;

    case PUBLIC = 'public';
    case AUTHENTICATED = 'authenticated';
    // case PRIVATE = 'private';

    public static function translation(): string
    {
        return 'post.visibilities.';
    }
}
