<?php

namespace App\Actions\Webhooks;

use App\Models\Project;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateWebhook
{
    /**
     * @param  array<string, mixed>  $data  url
     */
    public function handle(User $actor, Project $project, array $data): Webhook
    {
        Gate::forUser($actor)->authorize('manage', $project);

        $validated = Validator::make($data, [
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ])->validate();

        return $project->webhooks()->create([
            'url' => $validated['url'],
            'secret' => Str::random(40),
        ]);
    }
}
