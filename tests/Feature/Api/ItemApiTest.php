<?php

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->withMember($this->user)->create(['key' => 'BONKBOX', 'slug' => 'bonkbox']);
});

it('creates an item and returns its key', function () {
    Label::factory()->for($this->project)->create(['name' => 'ui']);
    Sanctum::actingAs($this->user, ['items:write']);

    $this->postJson('/api/v1/projects/bonkbox/items', [
        'type' => 'bug',
        'title' => 'Knop doet niets',
        'labels' => ['ui'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.key', 'BONKBOX-1')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.priority', 'medium')
        ->assertJsonPath('data.source', 'api')
        ->assertJsonPath('data.labels', ['ui'])
        ->assertJsonPath('data.allowed_transitions', ['in_progress']);
});

it('validates new items', function () {
    Sanctum::actingAs($this->user, ['items:write']);

    $this->postJson('/api/v1/projects/bonkbox/items', ['type' => 'chore'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title']);
});

it('needs items:write', function () {
    Sanctum::actingAs($this->user, ['items:read']);

    $this->postJson('/api/v1/projects/bonkbox/items', ['type' => 'bug', 'title' => 'X'])->assertForbidden();
});

it('filters and searches items', function () {
    Item::factory()->for($this->project)->create(['type' => 'bug', 'title' => 'Crash bij opslaan', 'priority' => 'high']);
    Item::factory()->for($this->project)->create(['type' => 'feature', 'title' => 'Donkere modus', 'priority' => 'low']);
    Item::factory()->for($this->project)->status(ItemStatus::Done)->create(['type' => 'bug', 'title' => 'Oud probleem', 'priority' => 'medium']);
    Sanctum::actingAs($this->user, ['items:read']);

    $this->getJson('/api/v1/projects/bonkbox/items?type=bug&status=open')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Crash bij opslaan');

    $this->getJson('/api/v1/projects/bonkbox/items?q=donker')
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.key', 'BONKBOX-2');

    $this->getJson('/api/v1/projects/bonkbox/items?q=BONKBOX-3')
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Oud probleem');

    $this->getJson('/api/v1/projects/bonkbox/items?sort=priority&direction=desc')
        ->assertJsonPath('data.0.priority', 'high');
});

it('paginates with a cursor', function () {
    Item::factory()->for($this->project)->count(3)->create();
    Sanctum::actingAs($this->user, ['items:read']);

    $response = $this->getJson('/api/v1/projects/bonkbox/items?per_page=2')
        ->assertJsonCount(2, 'data');

    $this->getJson($response->json('links.next'))->assertJsonCount(1, 'data');
});

it('shows an item by key with comments and activity', function () {
    $item = Item::factory()->for($this->project)->create();
    $item->comments()->create(['user_id' => $this->user->id, 'body' => 'Gezien']);
    Sanctum::actingAs($this->user, ['items:read']);

    $this->getJson('/api/v1/items/bonkbox-1')
        ->assertOk()
        ->assertJsonPath('data.key', 'BONKBOX-1')
        ->assertJsonPath('data.comments.0.body', 'Gezien');
});

it('returns 404 for unknown keys', function () {
    Sanctum::actingAs($this->user, ['items:read']);

    $this->getJson('/api/v1/items/BONKBOX-99')->assertNotFound();
    $this->getJson('/api/v1/items/nonsense')->assertNotFound();
});

it('updates an item', function () {
    Item::factory()->for($this->project)->create(['priority' => 'low']);
    Sanctum::actingAs($this->user, ['items:write']);

    $this->patchJson('/api/v1/items/BONKBOX-1', ['priority' => 'critical', 'assignee_id' => $this->user->id])
        ->assertOk()
        ->assertJsonPath('data.priority', 'critical')
        ->assertJsonPath('data.assignee.id', $this->user->id);
});

it('updates an item sent as POST with a method override', function () {
    Item::factory()->for($this->project)->create(['priority' => 'low']);
    Sanctum::actingAs($this->user, ['items:write']);

    $this->postJson('/api/v1/items/BONKBOX-1', ['assignee_id' => $this->user->id], ['X-HTTP-Method-Override' => 'PATCH'])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $this->user->id);
});

it('answers a request the web server refused with the refusal, not the route', function () {
    Item::factory()->for($this->project)->create();
    Sanctum::actingAs($this->user, ['items:read', 'items:write']);

    // Apache's ErrorDocument for a blocked PATCH: a GET to the same URL, marked as an error.
    $this->withServerVariables(['REDIRECT_STATUS' => '403', 'REDIRECT_REQUEST_METHOD' => 'PATCH'])
        ->getJson('/api/v1/items/BONKBOX-1')
        ->assertForbidden()
        ->assertJsonPath('message', 'The web server refused this PATCH request. Send it as POST with the header X-HTTP-Method-Override: PATCH.')
        ->assertJsonMissingPath('data');

    // mod_rewrite marks every request it hands to index.php with 200; those run as usual.
    $this->withServerVariables(['REDIRECT_STATUS' => '200'])
        ->getJson('/api/v1/items/BONKBOX-1')
        ->assertOk();
});

it('transitions an item and rejects skipped steps with 422', function () {
    Item::factory()->for($this->project)->create();
    Sanctum::actingAs($this->user, ['items:write']);

    $this->postJson('/api/v1/items/BONKBOX-1/transition', ['status' => 'done'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'Cannot move from open to done. Allowed: in_progress.');

    $this->postJson('/api/v1/items/BONKBOX-1/transition', ['status' => 'in_progress'])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');
});

it('adds and lists comments', function () {
    Item::factory()->for($this->project)->create();
    Sanctum::actingAs($this->user, ['items:read', 'comments:write']);

    $this->postJson('/api/v1/items/BONKBOX-1/comments', ['body' => 'Opgelost in build 12'])
        ->assertCreated()
        ->assertJsonPath('data.user.id', $this->user->id);

    $this->getJson('/api/v1/items/BONKBOX-1/comments')->assertJsonCount(1, 'data');
});

it('lets only the reporter or an owner delete', function () {
    Item::factory()->for($this->project)->create(['reporter_id' => User::factory()->create()->id]);
    Sanctum::actingAs($this->user, ['items:write']);

    $this->deleteJson('/api/v1/items/BONKBOX-1')->assertForbidden();
});

it('limits a project-scoped token to its project', function () {
    $other = Project::factory()->withMember($this->user)->create(['slug' => 'other']);
    $token = $this->user->createScopedToken('scoped', ['items:read', 'items:write'], $this->project)->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/projects/bonkbox/items')->assertOk();
    $this->withToken($token)->postJson('/api/v1/projects/other/items', ['type' => 'bug', 'title' => 'X'])->assertForbidden();
});
