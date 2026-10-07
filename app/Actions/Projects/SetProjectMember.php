<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Adds a member, or changes the role of an existing one.
 */
class SetProjectMember
{
    public function handle(User $actor, Project $project, User $member, ProjectRole $role): void
    {
        Gate::forUser($actor)->authorize('manage', $project);

        DB::transaction(function () use ($project, $member, $role) {
            if ($role !== ProjectRole::Owner && $project->isOwner($member) && $this->ownerCount($project) === 1) {
                throw ValidationException::withMessages(['role' => 'A project needs at least one owner.']);
            }

            $project->members()->syncWithoutDetaching([$member->id => ['role' => $role->value]]);
        });
    }

    private function ownerCount(Project $project): int
    {
        // Lock the project row so concurrent membership changes are serialised.
        Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();

        return $project->members()->wherePivot('role', ProjectRole::Owner->value)->count();
    }
}
