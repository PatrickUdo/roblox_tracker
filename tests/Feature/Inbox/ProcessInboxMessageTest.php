<?php

use App\Actions\Inbox\ConvertInboxMessage;
use App\Actions\Inbox\DiscardInboxMessage;
use App\Actions\Inbox\RetryInboxMessage;
use App\Actions\Inbox\SubmitInboxMessage;
use App\Enums\InboxStatus;
use App\Enums\ItemSource;
use App\Inbox\ClaudeInboxInterpreter;
use App\Inbox\InboxInterpreter;
use App\Inbox\InboxSuggestion;
use App\Inbox\InterpretationFailed;
use App\Jobs\ProcessInboxMessage;
use App\Models\InboxMessage;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

/**
 * Stands in for Claude: returns a fixed suggestion, or throws.
 */
function fakeInterpreter(?array $suggestion, ?Throwable $throw = null): void
{
    app()->instance(InboxInterpreter::class, new class($suggestion, $throw) implements InboxInterpreter
    {
        public function __construct(private ?array $suggestion, private ?Throwable $throw) {}

        public function interpret(string $text): InboxSuggestion
        {
            if ($this->throw) {
                throw $this->throw;
            }

            return ClaudeInboxInterpreter::suggestionFrom($this->suggestion);
        }
    });
}

beforeEach(function () {
    config(['inbox.threshold' => 0.8]);
    $this->voice = User::factory()->create();
    $this->project = Project::factory()->withMember($this->voice)->create(['key' => 'BONKBOX']);
    $this->message = $this->project->inboxMessages()->create([
        'user_id' => $this->voice->id,
        'reporter' => 'Jan',
        'raw_text' => 'De lift blijft hangen op de tweede verdieping.',
        'status' => InboxStatus::Pending,
    ]);
    $this->suggestion = [
        'type' => 'bug',
        'title' => 'Lift blijft hangen op tweede verdieping',
        'description' => "## Werkelijk\nDe lift stopt.",
        'priority' => 'high',
        'confidence' => 0.93,
    ];
});

it('creates an item above the threshold', function () {
    fakeInterpreter($this->suggestion);

    app()->call([new ProcessInboxMessage($this->message), 'handle']);

    $item = Item::sole();
    expect($this->message->fresh())
        ->status->toBe(InboxStatus::Converted)
        ->item_id->toBe($item->id)
        ->and($item)
        ->key->toBe('BONKBOX-1')
        ->source->toBe(ItemSource::Inbox)
        ->title->toBe('Lift blijft hangen op tweede verdieping')
        ->priority->value->toBe('high')
        ->and($item->description)->toContain('_Gemeld door Jan via de voice-agent._');
});

it('keeps a draft below the threshold', function () {
    fakeInterpreter([...$this->suggestion, 'confidence' => 0.4]);

    app()->call([new ProcessInboxMessage($this->message), 'handle']);

    expect(Item::count())->toBe(0)
        ->and($this->message->fresh())
        ->status->toBe(InboxStatus::Draft)
        ->suggestion->toMatchArray(['title' => 'Lift blijft hangen op tweede verdieping', 'confidence' => 0.4]);
});

it('marks unusable model output as failed without retrying', function () {
    fakeInterpreter(null, new InterpretationFailed('The model declined to interpret this message.'));

    app()->call([new ProcessInboxMessage($this->message), 'handle']);

    expect($this->message->fresh())
        ->status->toBe(InboxStatus::Failed)
        ->error->toBe('The model declined to interpret this message.');
});

it('marks the message failed after the last retry', function () {
    (new ProcessInboxMessage($this->message))->failed(new RuntimeException('Connection timed out'));

    expect($this->message->fresh())->status->toBe(InboxStatus::Failed)->error->toBe('Connection timed out');
});

it('ignores messages that are no longer pending', function () {
    $this->message->update(['status' => InboxStatus::Discarded]);
    fakeInterpreter($this->suggestion);

    app()->call([new ProcessInboxMessage($this->message), 'handle']);

    expect(Item::count())->toBe(0);
});

it('rejects incomplete suggestions', function () {
    ClaudeInboxInterpreter::suggestionFrom(['type' => 'chore', 'title' => 'X', 'priority' => 'low']);
})->throws(InterpretationFailed::class);

it('clamps confidence into 0..1', function () {
    expect(ClaudeInboxInterpreter::suggestionFrom([...$this->suggestion, 'confidence' => 7])->confidence)->toBe(1.0);
});

it('converts a draft by hand with edits', function () {
    $reviewer = User::factory()->create();
    $this->project->members()->attach($reviewer, ['role' => 'member']);
    $this->message->update(['status' => InboxStatus::Draft, 'suggestion' => $this->suggestion]);

    $item = app(ConvertInboxMessage::class)->handle($reviewer, $this->message, ['priority' => 'critical']);

    expect($item)->priority->value->toBe('critical')->reporter_id->toBe($reviewer->id)
        ->and($this->message->fresh()->status)->toBe(InboxStatus::Converted);

    app(ConvertInboxMessage::class)->handle($reviewer, $this->message->fresh());
})->throws(ValidationException::class);

it('discards and retries', function () {
    Queue::fake();
    $this->message->update(['status' => InboxStatus::Failed]);

    app(RetryInboxMessage::class)->handle($this->voice, $this->message);
    expect($this->message->fresh()->status)->toBe(InboxStatus::Pending);
    Queue::assertPushed(ProcessInboxMessage::class);

    $this->message->update(['status' => InboxStatus::Draft]);
    app(DiscardInboxMessage::class)->handle($this->voice, $this->message);
    expect($this->message->fresh()->status)->toBe(InboxStatus::Discarded);
});

it('runs end to end through the queue', function () {
    fakeInterpreter($this->suggestion);

    $message = app(SubmitInboxMessage::class)->handle($this->voice, $this->project, ['text' => 'Lift hangt', 'reporter' => 'Piet']);

    expect($message->fresh()->status)->toBe(InboxStatus::Converted)
        ->and(InboxMessage::count())->toBe(2);
});
