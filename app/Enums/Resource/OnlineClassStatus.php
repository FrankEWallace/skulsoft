<?php

namespace App\Enums\Resource;

use App\Concerns\HasEnum;

enum OnlineClassStatus: string
{
    use HasEnum;

    case PENDING = 'pending';
    case LIVE = 'live';
    case ENDED = 'ended';

    public static function translation(): string
    {
        return 'resource.online_class.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'info',
            self::LIVE => 'success',
            self::ENDED => 'danger',
        };
    }
}
