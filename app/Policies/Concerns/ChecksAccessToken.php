<?php

namespace App\Policies\Concerns;

use App\Enums\TokenAbility;
use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Contracts\HasAbilities;

trait ChecksAccessToken
{
    /**
     * Without an API token (web session, queued job) only membership counts.
     * With a token, it must carry the ability and, when scoped, match the project.
     */
    protected function tokenAllows(User $user, TokenAbility $ability, ?Project $project = null): bool
    {
        /** @var HasAbilities|null $token */
        $token = $user->currentAccessToken();

        if ($token === null) {
            return true;
        }

        if (! $token->can($ability->value)) {
            return false;
        }

        return $project === null || $this->tokenProjectId($user) === null || $this->tokenProjectId($user) === $project->id;
    }

    protected function tokenProjectId(User $user): ?int
    {
        return PersonalAccessToken::projectIdOf($user->currentAccessToken());
    }
}
