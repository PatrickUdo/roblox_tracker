<?php

namespace App\Policies;

use App\Models\InboxMessage;
use App\Models\User;

class InboxMessagePolicy
{
    /**
     * The sender (typically the voice agent) may always check on its own message.
     */
    public function view(User $user, InboxMessage $message): bool
    {
        return $message->user_id === $user->id
            || $user->can('viewInbox', $message->project);
    }
}
