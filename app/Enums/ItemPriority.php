<?php

namespace App\Enums;

enum ItemPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Laag',
            self::Medium => 'Normaal',
            self::High => 'Hoog',
            self::Critical => 'Kritiek',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'sky',
            self::High => 'amber',
            self::Critical => 'red',
        };
    }
}
