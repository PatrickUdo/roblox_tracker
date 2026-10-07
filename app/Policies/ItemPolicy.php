<?php

namespace App\Policies;

use App\Enums\TokenAbility;
use App\Models\Item;
use App\Models\User;
use App\Policies\Concerns\ChecksAccessToken;

class ItemPolicy
{
    use ChecksAccessToken;

    public function view(User $user, Item $item): bool
    {
        return $item->project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::ItemsRead, $item->project);
    }

    public function update(User $user, Item $item): bool
    {
        return $item->project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::ItemsWrite, $item->project);
    }

    public function transition(User $user, Item $item): bool
    {
        return $this->update($user, $item);
    }

    public function delete(User $user, Item $item): bool
    {
        return ($item->project->isOwner($user) || $item->reporter_id === $user->id)
            && $this->update($user, $item);
    }

    public function comment(User $user, Item $item): bool
    {
        return $item->project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::CommentsWrite, $item->project);
    }
}
