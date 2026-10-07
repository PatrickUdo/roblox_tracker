<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Eigenaar',
            self::Member => 'Lid',
        };
    }
}
