<?php

namespace App\Actions\Projects;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateProject
{
    /**
     * @param  array<string, mixed>  $data  name, key, slug?, description?
     */
    public function handle(User $actor, array $data): Project
    {
        Gate::forUser($actor)->authorize('create', Project::class);

        $data['key'] = strtoupper((string) ($data['key'] ?? ''));
        $data['slug'] ??= Str::slug((string) ($data['name'] ?? ''));

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            // Keys and slugs end up in item keys and URLs, so they cannot be changed later.
            'key' => ['required', 'string', 'regex:/^[A-Z][A-Z0-9]{1,9}$/', 'unique:projects,key'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:projects,slug'],
            'description' => ['nullable', 'string', 'max:10000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $validated) {
            $project = Project::create($validated);
            $project->members()->attach($actor->id, ['role' => ProjectRole::Owner->value]);

            return $project;
        });
    }
}
