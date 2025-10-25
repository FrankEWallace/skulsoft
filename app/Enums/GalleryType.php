<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum GalleryType: string
{
    use HasEnum;

    case EVENT_GALLERY = 'event_gallery';
    case MEDIA_GALLERY = 'media_gallery';

    public static function translation(): string
    {
        return 'gallery.types.';
    }
}
