<?php

use App\Actions\Items\DeleteItem;
use App\Actions\Items\UpdateItem;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->withMember($this->user)->create();
    $this->item = Item::factory()->for($this->project)->create(['title' => 'Oud', 'priority' => 'low']);
});

it('updates fields and records the diff', function () {
    app(UpdateItem::class)->handle($this->user, $this->item, ['title' => 'Nieuw', 'priority' => 'high']);

    expect($this->item->fresh())->title->toBe('Nieuw')
        ->and($this->item->activities()->sole())
        ->event->toBe('updated')
        ->old->toEqual(['title' => 'Oud', 'priority' => 'low'])
        ->new->toEqual(['title' => 'Nieuw', 'priority' => 'high']);
});

it('replaces labels and records them', function () {
    Label::factory()->for($this->project)->create(['name' => 'ui']);
    Label::factory()->for($this->project)->create(['name' => 'backend']);

    app(UpdateItem::class)->handle($this->user, $this->item, ['labels' => ['backend', 'ui']]);
    app(UpdateItem::class)->handle($this->user, $this->item, ['labels' => ['ui']]);

    expect($this->item->labels()->pluck('name')->all())->toBe(['ui'])
        ->and($this->item->activities()->latest('id')->first())
        ->old->toBe(['labels' => ['backend', 'ui']])
        ->new->toBe(['labels' => ['ui']]);
});

it('records nothing when nothing changes', function () {
    app(UpdateItem::class)->handle($this->user, $this->item, ['title' => 'Oud']);

    expect($this->item->activities()->count())->toBe(0);
});

it('ignores status, which only TransitionItem changes', function () {
    app(UpdateItem::class)->handle($this->user, $this->item, ['status' => 'closed']);

    expect($this->item->fresh()->status)->toBe(ItemStatus::Open);
});

it('lets the reporter or an owner delete, not other members', function () {
    $reporter = User::factory()->create();
    $owner = User::factory()->create();
    $this->project->members()->attach($reporter, ['role' => 'member']);
    $this->project->members()->attach($owner, ['role' => 'owner']);

    $mine = Item::factory()->for($this->project)->create(['reporter_id' => $reporter->id]);
    $other = Item::factory()->for($this->project)->create(['reporter_id' => $reporter->id]);

    app(DeleteItem::class)->handle($reporter, $mine);
    app(DeleteItem::class)->handle($owner, $other);
    expect(Item::whereKey([$mine->id, $other->id])->exists())->toBeFalse();

    app(DeleteItem::class)->handle($this->user, $this->item);
})->throws(AuthorizationException::class);
