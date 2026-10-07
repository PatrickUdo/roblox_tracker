<?php

use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->withOwner($this->user)->create(['key' => 'BONKBOX', 'slug' => 'bonkbox']);
});

it('requires a token', function () {
    $this->getJson('/api/v1/projects')->assertUnauthorized();
});

it('lists visible projects with open counts', function () {
    Item::factory()->for($this->project)->count(2)->create(['type' => 'bug']);
    Project::factory()->create();
    Sanctum::actingAs($this->user, ['projects:read']);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.key', 'BONKBOX')
        ->assertJsonPath('data.0.open_bugs', 2)
        ->assertJsonPath('data.0.url', route('projects.board', $this->project));
});

it('creates a project', function () {
    Sanctum::actingAs($this->user, ['projects:write']);

    $this->postJson('/api/v1/projects', ['name' => 'Werkplaats', 'key' => 'wp'])
        ->assertCreated()
        ->assertJsonPath('data.key', 'WP')
        ->assertJsonPath('data.slug', 'werkplaats');
});

it('validates new projects', function () {
    Sanctum::actingAs($this->user, ['projects:write']);

    $this->postJson('/api/v1/projects', ['name' => 'X', 'key' => 'BONKBOX'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('key');
});

it('needs projects:write to create', function () {
    Sanctum::actingAs($this->user, ['projects:read']);

    $this->postJson('/api/v1/projects', ['name' => 'X', 'key' => 'XX'])->assertForbidden();
});

it('shows a project with labels and members', function () {
    Sanctum::actingAs($this->user, ['projects:read']);

    $this->getJson('/api/v1/projects/bonkbox')
        ->assertOk()
        ->assertJsonPath('data.members.0.role', 'owner');
});

it('hides projects from non-members', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this->getJson('/api/v1/projects/bonkbox')->assertForbidden();
});

it('lets owners update name but not key', function () {
    Sanctum::actingAs($this->user, ['projects:write']);

    $this->patchJson('/api/v1/projects/bonkbox', ['name' => 'Nieuw', 'key' => 'OTHER'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nieuw')
        ->assertJsonPath('data.key', 'BONKBOX');
});

it('lets owners delete', function () {
    Sanctum::actingAs($this->user, ['projects:write']);

    $this->deleteJson('/api/v1/projects/bonkbox')->assertNoContent();
    expect(Project::count())->toBe(0);
});

it('reports the current token on /me', function () {
    // A real bearer token, so the token model (with its project) comes from the database.
    $new = $this->user->createScopedToken('voice', ['inbox:write'], $this->project);

    $this->withToken($new->plainTextToken)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('user.email', $this->user->email)
        ->assertJsonPath('token.abilities', ['inbox:write'])
        ->assertJsonPath('token.project', 'bonkbox');
});
