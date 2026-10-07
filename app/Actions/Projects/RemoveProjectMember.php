<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RemoveProjectMember
{
    public function handle(User $actor, Project $project, User $member): void
    {
        Gate::forUser($actor)->authorize('manage', $project);

        DB::transaction(function () use ($project, $member) {
            // Lock the project row so concurrent membership changes are serialised.
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $owners = $project->members()->wherePivot('role', ProjectRole::Owner->value)->count();

            if ($project->isOwner($member) && $owners === 1) {
                throw ValidationException::withMessages(['member' => 'A project needs at least one owner.']);
            }

            $project->members()->detach($member->id);
            $project->items()->where('assignee_id', $member->id)->update(['assignee_id' => null]);
        });
    }
}
