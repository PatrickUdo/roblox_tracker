<?php

namespace App\Actions\Inbox;

use App\Enums\InboxStatus;
use App\Jobs\ProcessInboxMessage;
use App\Models\InboxMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SubmitInboxMessage
{
    /**
     * @param  array<string, mixed>  $data  text, reporter?
     */
    public function handle(User $actor, Project $project, array $data): InboxMessage
    {
        Gate::forUser($actor)->authorize('submitInbox', $project);

        $validated = Validator::make($data, [
            'text' => ['required', 'string', 'max:20000'],
            'reporter' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $message = $project->inboxMessages()->create([
            'user_id' => $actor->id,
            'reporter' => $validated['reporter'] ?? null,
            'raw_text' => $validated['text'],
            'status' => InboxStatus::Pending,
        ]);

        ProcessInboxMessage::dispatch($message)->afterCommit();

        return $message;
    }
}
