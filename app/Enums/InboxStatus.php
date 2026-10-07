<?php

namespace App\Enums;

enum InboxStatus: string
{
    /** Waiting for the LLM job. */
    case Pending = 'pending';

    /** Interpreted, but below the confidence threshold: a person decides. */
    case Draft = 'draft';

    case Converted = 'converted';
    case Discarded = 'discarded';

    /** The LLM call failed or was refused; can be retried or converted by hand. */
    case Failed = 'failed';

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Draft, self::Failed], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Wordt verwerkt',
            self::Draft => 'Concept',
            self::Converted => 'Omgezet',
            self::Discarded => 'Weggegooid',
            self::Failed => 'Mislukt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'sky',
            self::Draft => 'amber',
            self::Converted => 'green',
            self::Discarded => 'zinc',
            self::Failed => 'red',
        };
    }
}
