<?php

namespace App\Actions\Labels;

use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateLabel
{
    /**
     * @param  array<string, mixed>  $data  name, color
     */
    public function handle(User $actor, Project $project, array $data): Label
    {
        Gate::forUser($actor)->authorize('manage', $project);

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:50', Rule::unique('labels')->where('project_id', $project->id)],
            'color' => ['required', 'string', 'hex_color', 'size:7'],
        ])->validate();

        return $project->labels()->create($validated);
    }
}
