<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateProject
{
    /**
     * Key and slug are deliberately immutable.
     *
     * @param  array<string, mixed>  $data  name?, description?
     */
    public function handle(User $actor, Project $project, array $data): Project
    {
        Gate::forUser($actor)->authorize('update', $project);

        $validated = Validator::make($data, [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ])->validate();

        $project->update($validated);

        return $project;
    }
}
