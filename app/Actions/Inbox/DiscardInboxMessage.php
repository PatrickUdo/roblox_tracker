<?php

namespace App\Actions\Inbox;

use App\Enums\InboxStatus;
use App\Models\InboxMessage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DiscardInboxMessage
{
    public function handle(User $actor, InboxMessage $message): void
    {
        Gate::forUser($actor)->authorize('reviewInbox', $message->project);

        if (! $message->status->isOpen()) {
            throw ValidationException::withMessages(['message' => 'This inbox message has already been handled.']);
        }

        $message->update(['status' => InboxStatus::Discarded]);
    }
}
