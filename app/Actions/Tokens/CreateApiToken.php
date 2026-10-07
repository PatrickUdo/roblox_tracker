<?php

namespace App\Actions\Tokens;

use App\Enums\TokenAbility;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\NewAccessToken;

class CreateApiToken
{
    /**
     * @param  array<string, mixed>  $data  name, abilities (list), project_id?
     */
    public function handle(User $actor, array $data): NewAccessToken
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'distinct', Rule::enum(TokenAbility::class)],
            'project_id' => ['nullable', 'integer', Rule::exists('project_user', 'project_id')->where('user_id', $actor->id)],
        ])->validate();

        $project = isset($validated['project_id']) ? Project::findOrFail($validated['project_id']) : null;

        return $actor->createScopedToken($validated['name'], array_values($validated['abilities']), $project);
    }
}
