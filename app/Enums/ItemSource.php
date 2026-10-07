<?php

namespace App\Enums;

enum ItemSource: string
{
    case Web = 'web';
    case Api = 'api';
    case Mcp = 'mcp';
    case Inbox = 'inbox';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Api => 'API',
            self::Mcp => 'MCP',
            self::Inbox => 'Voice-agent',
        };
    }
}
