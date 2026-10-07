<?php

namespace App\Enums;

enum TokenAbility: string
{
    case ProjectsRead = 'projects:read';
    case ProjectsWrite = 'projects:write';
    case ItemsRead = 'items:read';
    case ItemsWrite = 'items:write';
    case CommentsWrite = 'comments:write';
    case InboxWrite = 'inbox:write';

    public function label(): string
    {
        return match ($this) {
            self::ProjectsRead => 'Projecten lezen',
            self::ProjectsWrite => 'Projecten aanmaken en wijzigen',
            self::ItemsRead => 'Items lezen',
            self::ItemsWrite => 'Items aanmaken en wijzigen',
            self::CommentsWrite => 'Reacties plaatsen',
            self::InboxWrite => 'Inbox (voice-agent)',
        };
    }
}
