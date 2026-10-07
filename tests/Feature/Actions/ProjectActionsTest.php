<?php

use App\Actions\Labels\CreateLabel;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\RemoveProjectMember;
use App\Actions\Projects\SetProjectMember;
use App\Actions\Projects\UpdateProject;
use App\Enums\ProjectRole;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('creates a project with the creator as owner', function () {
    $project = app(CreateProject::class)->handle($this->owner, ['name' => 'Bonkbox Studios', 'key' => 'bonkbox']);

    expect($project)
        ->key->toBe('BONKBOX')
        ->slug->toBe('bonkbox-studios')
        ->and($project->roleOf($this->owner))->toBe(ProjectRole::Owner);
});

it('rejects invalid and duplicate keys', function (string $key) {
    Project::factory()->create(['key' => 'TAKEN']);

    app(CreateProject::class)->handle($this->owner, ['name' => 'X', 'key' => $key]);
})->with(['1ABC', 'A', 'WAY-TOO-LONG', 'TAKEN'])->throws(ValidationException::class);

it('never changes key or slug on update', function () {
    $project = Project::factory()->withOwner($this->owner)->create(['key' => 'BONKBOX', 'slug' => 'bonkbox']);

    app(UpdateProject::class)->handle($this->owner, $project, ['name' => 'Nieuw', 'key' => 'OTHER', 'slug' => 'other']);

    expect($project->fresh())->name->toBe('Nieuw')->key->toBe('BONKBOX')->slug->toBe('bonkbox');
});

it('lets only owners manage the project', function () {
    $member = User::factory()->create();
    $project = Project::factory()->withOwner($this->owner)->withMember($member)->create();

    app(CreateLabel::class)->handle($member, $project, ['name' => 'ui', 'color' => '#3b82f6']);
})->throws(AuthorizationException::class);

it('creates labels with a hex color', function () {
    $project = Project::factory()->withOwner($this->owner)->create();

    expect(app(CreateLabel::class)->handle($this->owner, $project, ['name' => 'ui', 'color' => '#3b82f6'])->name)->toBe('ui');

    app(CreateLabel::class)->handle($this->owner, $project, ['name' => 'ux', 'color' => 'blue']);
})->throws(ValidationException::class);

it('adds members and changes roles', function () {
    $project = Project::factory()->withOwner($this->owner)->create();
    $member = User::factory()->create();

    app(SetProjectMember::class)->handle($this->owner, $project, $member, ProjectRole::Member);
    expect($project->roleOf($member))->toBe(ProjectRole::Member);

    app(SetProjectMember::class)->handle($this->owner, $project, $member, ProjectRole::Owner);
    expect($project->roleOf($member))->toBe(ProjectRole::Owner);
});

it('keeps at least one owner', function () {
    $project = Project::factory()->withOwner($this->owner)->create();

    expect(fn () => app(SetProjectMember::class)->handle($this->owner, $project, $this->owner, ProjectRole::Member))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(RemoveProjectMember::class)->handle($this->owner, $project, $this->owner))
        ->toThrow(ValidationException::class);
});

it('unassigns items when a member is removed', function () {
    $member = User::factory()->create();
    $project = Project::factory()->withOwner($this->owner)->withMember($member)->create();
    $item = Item::factory()->for($project)->create(['assignee_id' => $member->id]);

    app(RemoveProjectMember::class)->handle($this->owner, $project, $member);

    expect($project->hasMember($member))->toBeFalse()
        ->and($item->fresh()->assignee_id)->toBeNull();
});
