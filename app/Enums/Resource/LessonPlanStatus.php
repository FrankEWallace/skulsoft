<?php

namespace App\Enums\Resource;

use App\Concerns\HasEnum;

enum LessonPlanStatus: string
{
    use HasEnum;

    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case NEEDS_IMPROVEMENT = 'needs_improvement';

    public static function translation(): string
    {
        return 'resource.lesson_plan.statuses.';
    }
}
