<?php

namespace App\Policies;

use App\Enums\TokenAbility;
use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\ChecksAccessToken;

class ProjectPolicy
{
    use ChecksAccessToken;

    public function viewAny(User $user): bool
    {
        return $this->tokenAllows($user, TokenAbility::ProjectsRead);
    }

    public function view(User $user, Project $project): bool
    {
        return $project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::ProjectsRead, $project);
    }

    public function create(User $user): bool
    {
        return $this->tokenAllows($user, TokenAbility::ProjectsWrite)
            && $this->tokenProjectId($user) === null;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isOwner($user)
            && $this->tokenAllows($user, TokenAbility::ProjectsWrite, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Members, roles and labels.
     */
    public function manage(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function createItem(User $user, Project $project): bool
    {
        return $project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::ItemsWrite, $project);
    }

    public function submitInbox(User $user, Project $project): bool
    {
        return $project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::InboxWrite, $project);
    }

    public function viewItems(User $user, Project $project): bool
    {
        return $project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::ItemsRead, $project);
    }

    public function viewInbox(User $user, Project $project): bool
    {
        return $this->viewItems($user, $project);
    }

    /**
     * Converting, discarding and retrying inbox messages.
     */
    public function reviewInbox(User $user, Project $project): bool
    {
        return $project->hasMember($user)
            && $this->tokenAllows($user, TokenAbility::ItemsWrite, $project);
    }
}
