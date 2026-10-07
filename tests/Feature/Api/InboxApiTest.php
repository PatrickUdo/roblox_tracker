<?php

use App\Enums\InboxStatus;
use App\Jobs\ProcessInboxMessage;
use App\Models\InboxMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->voice = User::factory()->create(['name' => 'Voice agent']);
    $this->project = Project::factory()->withMember($this->voice)->create(['key' => 'BONKBOX', 'slug' => 'bonkbox-studios']);
    $this->token = $this->voice->createScopedToken('voice', ['inbox:write'])->plainTextToken;
});

it('accepts free text with 202 and queues processing', function () {
    $response = $this->withToken($this->token)->postJson('/api/v1/inbox', [
        'project' => 'bonkbox-studios',
        'text' => 'De lift op locatie Noord blijft hangen op de tweede verdieping.',
        'reporter' => 'Jan de monteur',
    ]);

    $response->assertAccepted()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.reporter', 'Jan de monteur');

    $message = InboxMessage::sole();
    expect($response->json('data.inbox_id'))->toBe($message->id);
    Queue::assertPushed(ProcessInboxMessage::class, fn ($job) => $job->message->is($message));
});

it('accepts the project key as well', function () {
    $this->withToken($this->token)->postJson('/api/v1/inbox', ['project' => 'bonkbox', 'text' => 'Test'])->assertAccepted();
});

it('lets the sender poll its own message without items:read', function () {
    $message = $this->project->inboxMessages()->create([
        'user_id' => $this->voice->id, 'raw_text' => 'x', 'status' => InboxStatus::Draft,
    ]);

    $this->withToken($this->token)->getJson("/api/v1/inbox/{$message->id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');
});

it('requires inbox:write', function () {
    $token = $this->voice->createScopedToken('reader', ['items:read'])->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/inbox', ['project' => 'bonkbox', 'text' => 'Test'])->assertForbidden();
});

it('validates the payload', function () {
    $this->withToken($this->token)->postJson('/api/v1/inbox', ['project' => 'bonkbox'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('text');

    $this->withToken($this->token)->postJson('/api/v1/inbox', ['project' => 'nope', 'text' => 'x'])->assertNotFound();
});
