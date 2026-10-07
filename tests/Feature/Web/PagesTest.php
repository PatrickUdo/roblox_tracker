<?php

use App\Enums\InboxStatus;
use App\Enums\ItemStatus;
use App\Livewire\Items\ItemForm;
use App\Livewire\Items\Show;
use App\Livewire\Projects\Board;
use App\Livewire\Projects\Inbox;
use App\Livewire\Projects\Index;
use App\Livewire\Projects\ItemList;
use App\Livewire\Projects\Settings;
use App\Livewire\Settings\Tokens;
use App\Models\Item;
use App\Models\Label;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Patrick']);
    $this->project = Project::factory()->withOwner($this->user)->create(['name' => 'Bonkbox Studios', 'key' => 'BONKBOX', 'slug' => 'bonkbox-studios']);
    $this->actingAs($this->user);
});

it('renders every page', function () {
    $item = Item::factory()->for($this->project)->create(['description' => '**Vet** <script>alert(1)</script>']);
    $item->comments()->create(['user_id' => $this->user->id, 'body' => 'Reactie']);

    $this->get('/projects')->assertOk()->assertSee('Bonkbox Studios');
    $this->get('/projects/bonkbox-studios')->assertOk()->assertSee('BONKBOX-1');
    $this->get('/projects/bonkbox-studios/list')->assertOk()->assertSee($item->title);
    $this->get('/projects/bonkbox-studios/inbox')->assertOk();
    $this->get('/projects/bonkbox-studios/settings')->assertOk();
    $this->get('/items/BONKBOX-1')->assertOk()
        ->assertSee('<strong>Vet</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false);
    $this->get('/settings/tokens')->assertOk();
});

it('keeps non-members out', function () {
    $this->actingAs(User::factory()->create());
    Item::factory()->for($this->project)->create();

    $this->get('/projects/bonkbox-studios')->assertForbidden();
    $this->get('/items/BONKBOX-1')->assertForbidden();
    $this->get('/projects/bonkbox-studios/settings')->assertForbidden();
});

it('lets members see the board but not the settings', function () {
    $member = User::factory()->create();
    $this->project->members()->attach($member, ['role' => 'member']);
    $this->actingAs($member);

    $this->get('/projects/bonkbox-studios')->assertOk()->assertDontSee('Instellingen');
    $this->get('/projects/bonkbox-studios/settings')->assertForbidden();
});

it('creates a project and suggests a key', function () {
    Livewire::test(Index::class)
        ->set('name', 'Werkplaats Noord')
        ->assertSet('key', 'WERKPLAATS')
        ->set('key', 'WPN')
        ->call('create')
        ->assertRedirect(route('projects.board', 'werkplaats-noord'));

    expect(Project::where('key', 'WPN')->sole()->isOwner($this->user))->toBeTrue();
});

it('shows validation errors when creating a project', function () {
    Livewire::test(Index::class)
        ->set('name', 'Dubbel')
        ->set('key', 'BONKBOX')
        ->call('create')
        ->assertHasErrors('key');
});

it('moves cards on the board following the transition rules', function () {
    Item::factory()->for($this->project)->create();

    Livewire::test(Board::class, ['project' => $this->project])
        ->call('moveItem', 'BONKBOX-1', 'done')
        ->call('moveItem', 'BONKBOX-1', 'in_progress');

    expect(Item::sole()->status)->toBe(ItemStatus::InProgress);
});

it('filters the board', function () {
    Item::factory()->for($this->project)->create(['type' => 'bug', 'title' => 'Een bug']);
    Item::factory()->for($this->project)->create(['type' => 'feature', 'title' => 'Een feature']);

    Livewire::test(Board::class, ['project' => $this->project])
        ->set('type', 'feature')
        ->assertSee('Een feature')
        ->assertDontSee('Een bug');
});

it('creates and edits items in the modal', function () {
    Label::factory()->for($this->project)->create(['name' => 'ui']);

    Livewire::test(ItemForm::class, ['project' => $this->project])
        ->call('create', 'feature')
        ->set('title', 'Exporteren naar CSV')
        ->set('labels', ['ui'])
        ->set('assignee_id', (string) $this->user->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('item-saved', key: 'BONKBOX-1');

    expect(Item::sole())->type->value->toBe('feature')->assignee_id->toBe($this->user->id);

    Livewire::test(ItemForm::class, ['project' => $this->project])
        ->call('edit', 'BONKBOX-1')
        ->assertSet('title', 'Exporteren naar CSV')
        ->assertSet('labels', ['ui'])
        ->set('title', 'Exporteren naar Excel')
        ->call('save');

    expect(Item::sole()->title)->toBe('Exporteren naar Excel');
});

it('validates the item form', function () {
    Livewire::test(ItemForm::class, ['project' => $this->project])
        ->call('create')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});

it('changes status, assigns and comments on the item page', function () {
    $item = Item::factory()->for($this->project)->create();

    Livewire::test(Show::class, ['item' => $item])
        ->call('changeStatus', 'in_progress')
        ->call('assign', (string) $this->user->id)
        ->set('body', 'Ik pak dit op')
        ->call('comment')
        ->assertSet('body', '')
        ->assertSee('Ik pak dit op');

    expect($item->fresh())
        ->status->toBe(ItemStatus::InProgress)
        ->assignee_id->toBe($this->user->id)
        ->and($item->comments()->count())->toBe(1);
});

it('deletes an item and returns to the board', function () {
    $item = Item::factory()->for($this->project)->create(['reporter_id' => $this->user->id]);

    Livewire::test(Show::class, ['item' => $item])
        ->call('delete')
        ->assertRedirect(route('projects.board', $this->project));

    expect(Item::count())->toBe(0);
});

it('searches and sorts the list', function () {
    Item::factory()->for($this->project)->create(['title' => 'Crash bij opslaan']);
    Item::factory()->for($this->project)->create(['title' => 'Donkere modus']);

    Livewire::test(ItemList::class, ['project' => $this->project])
        ->set('q', 'crash')
        ->assertSee('Crash bij opslaan')
        ->assertDontSee('Donkere modus')
        ->call('sortBy', 'title')
        ->assertSet('sort', 'title');
});

it('converts and discards inbox drafts', function () {
    $draft = $this->project->inboxMessages()->create([
        'raw_text' => 'Lift hangt', 'status' => InboxStatus::Draft,
        'suggestion' => ['type' => 'bug', 'title' => 'Lift hangt', 'description' => 'x', 'priority' => 'high', 'confidence' => 0.5],
    ]);
    $other = $this->project->inboxMessages()->create(['raw_text' => 'Onzin', 'status' => InboxStatus::Draft]);

    Livewire::test(Inbox::class, ['project' => $this->project])
        ->assertSee('50% zeker')
        ->call('startConvert', $draft->id)
        ->assertSet('title', 'Lift hangt')
        ->assertSet('priority', 'high')
        ->call('convert')
        ->call('discard', $other->id);

    expect(Item::sole()->title)->toBe('Lift hangt')
        ->and($draft->fresh()->status)->toBe(InboxStatus::Converted)
        ->and($other->fresh()->status)->toBe(InboxStatus::Discarded);
});

it('manages members, labels and webhooks', function () {
    $colleague = User::factory()->create(['email' => 'collega@example.com']);

    Livewire::test(Settings::class, ['project' => $this->project])
        ->set('memberEmail', 'onbekend@example.com')
        ->call('addMember')
        ->assertHasErrors('memberEmail')
        ->set('memberEmail', 'collega@example.com')
        ->call('addMember')
        ->assertHasNoErrors()
        ->call('changeRole', $colleague->id, 'owner')
        ->set('labelName', 'backend')
        ->set('labelColor', '#10b981')
        ->call('addLabel')
        ->set('webhookUrl', 'https://hooks.example.com/x')
        ->call('addWebhook')
        ->assertNotSet('newWebhookSecret', null)
        ->set('name', 'Bonkbox')
        ->call('saveGeneral');

    expect($this->project->fresh())
        ->name->toBe('Bonkbox')
        ->isOwner($colleague)->toBeTrue()
        ->and($this->project->labels()->pluck('name')->all())->toBe(['backend'])
        ->and($this->project->webhooks()->count())->toBe(1);
});

it('deletes a project only after typing its key', function () {
    Livewire::test(Settings::class, ['project' => $this->project])
        ->set('confirmKey', 'nee')
        ->call('deleteProject')
        ->assertHasErrors('confirmKey')
        ->set('confirmKey', 'BONKBOX')
        ->call('deleteProject')
        ->assertRedirect(route('projects.index'));

    expect(Project::count())->toBe(0);
});

it('creates, shows once and revokes tokens', function () {
    $component = Livewire::test(Tokens::class)
        ->set('name', 'Claude Code')
        ->call('preset', 'voice')
        ->set('project_id', (string) $this->project->id)
        ->call('create')
        ->assertHasNoErrors();

    $plain = $component->get('plainTextToken');
    $token = PersonalAccessToken::findToken($plain);

    expect($token)
        ->abilities->toBe(['inbox:write'])
        ->project_id->toBe($this->project->id);

    $component->assertSee('claude mcp add --transport http tracker')
        ->call('forgetToken')
        ->assertSet('plainTextToken', null)
        ->call('revoke', $token->id);

    expect(PersonalAccessToken::count())->toBe(0);
});

it('does not scope tokens to projects the user is not in', function () {
    Livewire::test(Tokens::class)
        ->set('name', 'X')
        ->set('project_id', (string) Project::factory()->create()->id)
        ->call('create')
        ->assertHasErrors('project_id');
});
