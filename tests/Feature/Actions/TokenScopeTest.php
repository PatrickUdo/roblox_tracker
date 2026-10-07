<?php

use App\Actions\Items\CreateItem;
use App\Actions\Projects\CreateProject;
use App\Enums\ItemSource;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->withMember($this->user)->create();
    $this->other = Project::factory()->withMember($this->user)->create();
    $this->createIn = fn (Project $project) => app(CreateItem::class)
        ->handle($this->user, $project, ['type' => 'bug', 'title' => 'Test'], ItemSource::Mcp);
});

it('allows members without a token (web session or job)', function () {
    expect(($this->createIn)($this->project))->toBeInstanceOf(Item::class);
});

it('requires the matching ability on the token', function () {
    withToken($this->user, ['items:read']);

    ($this->createIn)($this->project);
})->throws(AuthorizationException::class);

it('limits a project token to its project, despite membership', function () {
    withToken($this->user, ['items:read', 'items:write'], $this->project);

    expect(($this->createIn)($this->project))->toBeInstanceOf(Item::class);

    ($this->createIn)($this->other);
})->throws(AuthorizationException::class);

it('lets an inbox-only token read nothing', function () {
    $item = Item::factory()->for($this->project)->create();
    withToken($this->user, ['inbox:write']);

    expect(Gate::forUser($this->user)->allows('view', $item))->toBeFalse()
        ->and(Gate::forUser($this->user)->allows('view', $this->project))->toBeFalse();
});

it('narrows visible projects to the token project', function () {
    Project::factory()->create();

    expect(Project::visibleTo($this->user)->count())->toBe(2);

    withToken($this->user, ['projects:read'], $this->project);

    expect(Project::visibleTo($this->user)->pluck('id')->all())->toBe([$this->project->id]);
});

it('does not let a project token create projects', function () {
    withToken($this->user, ['*'], $this->project);

    app(CreateProject::class)->handle($this->user, ['name' => 'Nieuw', 'key' => 'NEW']);
})->throws(AuthorizationException::class);
