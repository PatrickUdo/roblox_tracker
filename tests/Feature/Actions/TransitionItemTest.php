<?php

use App\Actions\Items\TransitionItem;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->withMember($this->user)->create();
    $this->transition = fn (Item $item, ItemStatus|string $to, ?User $actor = null) => app(TransitionItem::class)->handle($actor ?? $this->user, $item, $to);
});

it('moves an item forward', function () {
    $item = Item::factory()->for($this->project)->create();

    ($this->transition)($item, ItemStatus::InProgress);

    expect($item->fresh()->status)->toBe(ItemStatus::InProgress);
});

it('rejects a skipped step with the allowed statuses', function () {
    $item = Item::factory()->for($this->project)->create();

    try {
        ($this->transition)($item, ItemStatus::Done);
        $this->fail('Expected a validation error.');
    } catch (ValidationException $e) {
        expect($e->errors()['status'][0])->toBe('Cannot move from open to done. Allowed: in_progress.');
    }

    expect($item->fresh()->status)->toBe(ItemStatus::Open);
});

it('sets closed_at on close and clears it on reopen', function () {
    $item = Item::factory()->for($this->project)->status(ItemStatus::Done)->create();

    ($this->transition)($item, ItemStatus::Closed);
    expect($item->fresh()->closed_at)->not->toBeNull();

    ($this->transition)($item, ItemStatus::Open);
    expect($item->fresh()->closed_at)->toBeNull();
});

it('accepts a status string and rejects unknown ones', function () {
    $item = Item::factory()->for($this->project)->create();

    ($this->transition)($item, 'in_progress');
    expect($item->fresh()->status)->toBe(ItemStatus::InProgress);

    ($this->transition)($item, 'archived');
})->throws(ValidationException::class);

it('checks the stored status, not a stale model', function () {
    $item = Item::factory()->for($this->project)->create();
    $stale = Item::find($item->id);

    ($this->transition)($item, ItemStatus::InProgress);
    ($this->transition)($stale, ItemStatus::InProgress);
})->throws(ValidationException::class);

it('records the transition', function () {
    $item = Item::factory()->for($this->project)->create();

    ($this->transition)($item, ItemStatus::InProgress);

    expect($item->activities()->sole())
        ->event->toBe('transitioned')
        ->old->toBe(['status' => 'open'])
        ->new->toBe(['status' => 'in_progress']);
});

it('refuses non-members', function () {
    $item = Item::factory()->for($this->project)->create();

    ($this->transition)($item, ItemStatus::InProgress, User::factory()->create());
})->throws(AuthorizationException::class);
