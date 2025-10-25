<?php

namespace App\Enums\Blog;

use App\Concerns\HasEnum;

enum Status: string
{
    use HasEnum;

    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public static function getIcon($type): string
    {
        return match ($type) {
            self::DRAFT->value => 'fas fa-pen-to-square',
            self::PUBLISHED->value => 'far fa-check-circle',
        };
    }

    public static function translation(): string
    {
        return 'blog.statuses.';
    }

    public static function getOptions(): array
    {
        $options = [];

        foreach (self::cases() as $option) {
            $options[] = [
                'label' => trans(self::translation().$option->value),
                'value' => $option->value,
                'icon' => self::getIcon($option->value),
            ];
        }

        return $options;
    }
}
