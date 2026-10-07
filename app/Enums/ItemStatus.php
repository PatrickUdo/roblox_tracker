<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Closed = 'closed';

    /**
     * Forward one step at a time (open → in_progress → done → closed),
     * or back to open from any other status.
     */
    public function canTransitionTo(self $to): bool
    {
        if ($to === $this) {
            return false;
        }

        if ($to === self::Open) {
            return true;
        }

        return match ($this) {
            self::Open => $to === self::InProgress,
            self::InProgress => $to === self::Done,
            self::Done => $to === self::Closed,
            self::Closed => false,
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return array_values(array_filter(self::cases(), fn (self $to) => $this->canTransitionTo($to)));
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'Bezig',
            self::Done => 'Klaar',
            self::Closed => 'Gesloten',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'zinc',
            self::InProgress => 'blue',
            self::Done => 'green',
            self::Closed => 'zinc',
        };
    }
}
