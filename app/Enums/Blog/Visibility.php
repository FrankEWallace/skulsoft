<?php

namespace App\Enums\Blog;

use App\Concerns\HasEnum;

enum Visibility: string
{
    use HasEnum;

    case PUBLIC = 'public';
    case AUTHENTICATED = 'authenticated';
    case MEMBER = 'member';

    public static function translation(): string
    {
        return 'blog.visibilities.';
    }
}
