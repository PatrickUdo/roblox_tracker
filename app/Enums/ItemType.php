<?php

namespace App\Enums;

enum ItemType: string
{
    case Bug = 'bug';
    case Feature = 'feature';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Feature => 'Feature',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bug => 'red',
            self::Feature => 'violet',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Bug => 'bug-ant',
            self::Feature => 'sparkles',
        };
    }
}
