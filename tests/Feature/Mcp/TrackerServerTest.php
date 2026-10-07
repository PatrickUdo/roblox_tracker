<?php

use App\Enums\ItemSource;
use App\Enums\ItemStatus;
use App\Mcp\Prompts\TriageBug;
use App\Mcp\Resources\ItemResource;
use App\Mcp\Resources\ProjectResource;
use App\Mcp\Servers\TrackerServer;
use App\Mcp\Tools\AddComment;
use App\Mcp\Tools\CreateItem;
use App\Mcp\Tools\GetItem;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\SearchItems;
use App\Mcp\Tools\TransitionItem;
use App\Mcp\Tools\UpdateItem;
use App\Models\Item;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'dev@example.com']);
    $this->project = Project::factory()->withMember($this->user)->create(['name' => 'Bonkbox Studios', 'key' => 'BONKBOX', 'slug' => 'bonkbox-studios']);
});

it('lists projects', function () {
    Item::factory()->for($this->project)->create(['type' => 'bug']);

    TrackerServer::actingAs($this->user)->tool(ListProjects::class)
        ->assertOk()
        ->assertSee('Bonkbox Studios (slug: bonkbox-studios, key: BONKBOX): 1 open bugs, 0 open features');
});

it('creates an item through the shared action', function () {
    Label::factory()->for($this->project)->create(['name' => 'ui']);

    TrackerServer::actingAs($this->user)->tool(CreateItem::class, [
        'project' => 'BONKBOX',
        'type' => 'bug',
        'title' => 'Scherm blijft wit',
        'description' => 'Na inloggen blijft het scherm wit.',
        'priority' => 'high',
        'labels' => ['ui'],
        'assignee' => 'dev@example.com',
    ])->assertOk()->assertSee('Created BONKBOX-1: Scherm blijft wit');

    expect(Item::sole())
        ->source->toBe(ItemSource::Mcp)
        ->assignee_id->toBe($this->user->id)
        ->and(Item::sole()->labels->pluck('name')->all())->toBe(['ui']);
});

it('returns validation errors as tool errors', function () {
    TrackerServer::actingAs($this->user)->tool(CreateItem::class, [
        'project' => 'bonkbox-studios',
        'type' => 'chore',
        'title' => 'X',
        'description' => 'Y',
    ])->assertHasErrors();

    expect(Item::count())->toBe(0);
});

it('reports unknown projects and items clearly', function () {
    TrackerServer::actingAs($this->user)->tool(CreateItem::class, ['project' => 'nope', 'type' => 'bug', 'title' => 'X', 'description' => 'Y'])
        ->assertHasErrors(['Project nope not found. Use list_projects to see the available projects.']);

    TrackerServer::actingAs($this->user)->tool(GetItem::class, ['key' => 'BONKBOX-99'])
        ->assertHasErrors(['Item BONKBOX-99 not found. Item keys look like PROJECTKEY-42.']);
});

it('searches items', function () {
    Item::factory()->for($this->project)->create(['title' => 'Crash bij opslaan', 'type' => 'bug']);
    Item::factory()->for($this->project)->create(['title' => 'Donkere modus', 'type' => 'feature']);

    TrackerServer::actingAs($this->user)->tool(SearchItems::class, ['query' => 'crash'])
        ->assertOk()
        ->assertSee('BONKBOX-1 · bug · open')
        ->assertDontSee('Donkere modus');

    TrackerServer::actingAs($this->user)->tool(SearchItems::class, ['project' => 'bonkbox-studios', 'type' => 'feature'])
        ->assertSee('Donkere modus')
        ->assertDontSee('Crash');
});

it('gets a full item', function () {
    $item = Item::factory()->for($this->project)->create(['title' => 'Crash', 'description' => 'Stappen: klik op opslaan']);
    $item->comments()->create(['user_id' => $this->user->id, 'body' => 'Kan ik reproduceren']);

    TrackerServer::actingAs($this->user)->tool(GetItem::class, ['key' => 'BONKBOX-1'])
        ->assertOk()
        ->assertSee(['# BONKBOX-1: Crash', 'allowed next: in_progress', 'Stappen: klik op opslaan', 'Kan ik reproduceren']);
});

it('updates an item and can unassign', function () {
    Item::factory()->for($this->project)->create(['assignee_id' => $this->user->id, 'priority' => 'low']);

    TrackerServer::actingAs($this->user)->tool(UpdateItem::class, ['key' => 'BONKBOX-1', 'priority' => 'critical', 'assignee' => 'none'])
        ->assertOk();

    expect(Item::sole())->priority->value->toBe('critical')->assignee_id->toBeNull();
});

it('transitions with the same rules as the API', function () {
    Item::factory()->for($this->project)->create();

    TrackerServer::actingAs($this->user)->tool(TransitionItem::class, ['key' => 'BONKBOX-1', 'status' => 'closed'])
        ->assertHasErrors(['Cannot move from open to closed. Allowed: in_progress.']);

    TrackerServer::actingAs($this->user)->tool(TransitionItem::class, ['key' => 'BONKBOX-1', 'status' => 'in_progress'])
        ->assertSee('BONKBOX-1 is now in_progress.');

    expect(Item::sole()->status)->toBe(ItemStatus::InProgress);
});

it('adds a comment', function () {
    Item::factory()->for($this->project)->create();

    TrackerServer::actingAs($this->user)->tool(AddComment::class, ['key' => 'BONKBOX-1', 'body' => 'Opgelost'])
        ->assertSee('Comment added to BONKBOX-1.');

    expect(Item::sole()->comments()->sole()->body)->toBe('Opgelost');
});

it('respects token abilities and project scope', function () {
    $other = Project::factory()->withMember($this->user)->create(['key' => 'OTHER']);
    Item::factory()->for($other)->create(['title' => 'Geheim']);
    withToken($this->user, ['items:read'], $this->project);

    TrackerServer::actingAs($this->user)->tool(CreateItem::class, ['project' => 'BONKBOX', 'type' => 'bug', 'title' => 'X', 'description' => 'Y'])
        ->assertHasErrors();

    TrackerServer::actingAs($this->user)->tool(GetItem::class, ['key' => 'OTHER-1'])
        ->assertHasErrors();

    TrackerServer::actingAs($this->user)->tool(SearchItems::class, ['query' => 'Geheim'])
        ->assertSee('No items found.');
});

it('refuses non-members', function () {
    Item::factory()->for($this->project)->create();

    TrackerServer::actingAs(User::factory()->create())->tool(GetItem::class, ['key' => 'BONKBOX-1'])
        ->assertHasErrors();
});

it('serves project and item resources', function () {
    Item::factory()->for($this->project)->create(['title' => 'Open ding']);

    TrackerServer::actingAs($this->user)->resource(ProjectResource::class, ['slug' => 'bonkbox-studios'])
        ->assertOk()
        ->assertSee(['# Bonkbox Studios (BONKBOX)', 'BONKBOX-1 · ', 'Open ding']);

    TrackerServer::actingAs($this->user)->resource(ItemResource::class, ['key' => 'BONKBOX-1'])
        ->assertSee('# BONKBOX-1: Open ding');
});

it('offers the triage prompt', function () {
    TrackerServer::actingAs($this->user)->prompt(TriageBug::class, ['report' => 'app crasht bij opslaan', 'project' => 'bonkbox-studios'])
        ->assertOk()
        ->assertSee(['in project "bonkbox-studios"', 'create_item', 'app crasht bij opslaan']);
});

it('requires a token over HTTP', function () {
    $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
});

it('lists the tools over HTTP with a token', function () {
    $token = $this->user->createScopedToken('agent', ['items:read'])->plainTextToken;

    $response = $this->withToken($token)
        ->withHeaders(['Accept' => 'application/json, text/event-stream'])
        ->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
            'protocolVersion' => '2025-06-18', 'capabilities' => new stdClass, 'clientInfo' => ['name' => 'test', 'version' => '1'],
        ]]);

    $response->assertOk()->assertJsonPath('result.serverInfo.name', 'Bonkbox Tracker');
});
