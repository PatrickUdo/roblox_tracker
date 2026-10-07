<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteProject
{
    public function handle(User $actor, Project $project): void
    {
        Gate::forUser($actor)->authorize('delete', $project);

        $project->delete();
    }
}
