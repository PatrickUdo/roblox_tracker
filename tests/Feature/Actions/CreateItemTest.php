<?php

use App\Actions\Items\CreateItem;
use App\Enums\ItemPriority;
use App\Enums\ItemSource;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->withMember($this->user)->create(['key' => 'BONKBOX']);
    $this->create = fn (array $data = [], ?Project $project = null, ?User $actor = null) => app(CreateItem::class)->handle(
        $actor ?? $this->user,
        $project ?? $this->project,
        [...['type' => 'bug', 'title' => 'Crash bij opslaan'], ...$data],
        ItemSource::Api,
    );
});

it('numbers items per project', function () {
    $other = Project::factory()->withMember($this->user)->create();

    $first = ($this->create)();
    $second = ($this->create)();
    $elsewhere = ($this->create)([], $other);

    expect($first->number)->toBe(1)
        ->and($second->number)->toBe(2)
        ->and($second->key)->toBe('BONKBOX-2')
        ->and($elsewhere->number)->toBe(1)
        ->and($this->project->fresh()->next_number)->toBe(3);
});

it('keeps numbering after an item is deleted', function () {
    ($this->create)()->delete();

    expect(($this->create)()->number)->toBe(2);
});

it('fills defaults from the actor and source', function () {
    $item = ($this->create)();

    expect($item->status)->toBe(ItemStatus::Open)
        ->and($item->priority)->toBe(ItemPriority::Medium)
        ->and($item->reporter_id)->toBe($this->user->id)
        ->and($item->source)->toBe(ItemSource::Api);
});

it('attaches labels by name', function () {
    Label::factory()->for($this->project)->create(['name' => 'ui']);
    Label::factory()->for($this->project)->create(['name' => 'backend']);

    $item = ($this->create)(['labels' => ['ui']]);

    expect($item->labels->pluck('name')->all())->toBe(['ui']);
});

it('rejects labels from another project', function () {
    Label::factory()->create(['name' => 'ui']);

    ($this->create)(['labels' => ['ui']]);
})->throws(ValidationException::class);

it('only assigns project members', function () {
    $outsider = User::factory()->create();

    ($this->create)(['assignee_id' => $outsider->id]);
})->throws(ValidationException::class);

it('validates type and title', function () {
    ($this->create)(['type' => 'chore', 'title' => '']);
})->throws(ValidationException::class);

it('refuses non-members', function () {
    ($this->create)([], null, User::factory()->create());
})->throws(AuthorizationException::class);

it('records a created activity', function () {
    $item = ($this->create)();

    expect($item->activities()->sole())
        ->event->toBe('created')
        ->user_id->toBe($this->user->id)
        ->new->toBe(['status' => 'open', 'source' => 'api']);
});

it('resolves items by key', function () {
    $item = ($this->create)();

    expect(Item::findByKeyOrFail('BONKBOX-1')->is($item))->toBeTrue()
        ->and(Item::findByKeyOrFail('bonkbox-1')->is($item))->toBeTrue();
});

it('fails on unknown or malformed keys', function (string $key) {
    ($this->create)();

    Item::findByKeyOrFail($key);
})->with(['BONKBOX-2', 'OTHER-1', 'BONKBOX', '42', 'BONKBOX-1-2'])
    ->throws(ModelNotFoundException::class);
