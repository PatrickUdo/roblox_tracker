<?php

namespace App\Actions\Inbox;

use App\Enums\InboxStatus;
use App\Jobs\ProcessInboxMessage;
use App\Models\InboxMessage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RetryInboxMessage
{
    public function handle(User $actor, InboxMessage $message): void
    {
        Gate::forUser($actor)->authorize('reviewInbox', $message->project);

        if ($message->status !== InboxStatus::Failed) {
            throw ValidationException::withMessages(['message' => 'Only failed messages can be retried.']);
        }

        $message->update(['status' => InboxStatus::Pending, 'error' => null]);

        ProcessInboxMessage::dispatch($message);
    }
}
