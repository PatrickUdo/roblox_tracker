<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\TriageBug;
use App\Mcp\Resources\ItemResource;
use App\Mcp\Resources\ProjectResource;
use App\Mcp\Tools\AddComment;
use App\Mcp\Tools\CreateItem;
use App\Mcp\Tools\GetItem;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\SearchItems;
use App\Mcp\Tools\TransitionItem;
use App\Mcp\Tools\UpdateItem;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Bonkbox Tracker')]
#[Version('1.0.0')]
#[Instructions(<<<'MD'
    Issue tracker for bugs and features, organised per project. Items are addressed by key, such as BONKBOX-42.
    Start with list_projects, or read the project://{slug} resource for a project overview with its open items.
    Search before creating, to avoid duplicates. Status moves one step at a time
    (open → in_progress → done → closed) and can go back to open from any status. People are named by e-mail address.
    MD)]
class TrackerServer extends Server
{
    protected array $tools = [
        ListProjects::class,
        SearchItems::class,
        GetItem::class,
        CreateItem::class,
        UpdateItem::class,
        TransitionItem::class,
        AddComment::class,
    ];

    protected array $resources = [
        ProjectResource::class,
        ItemResource::class,
    ];

    protected array $prompts = [
        TriageBug::class,
    ];
}
