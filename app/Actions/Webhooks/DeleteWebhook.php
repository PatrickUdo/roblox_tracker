<?php

namespace App\Actions\Webhooks;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\Gate;

class DeleteWebhook
{
    public function handle(User $actor, Webhook $webhook): void
    {
        Gate::forUser($actor)->authorize('manage', $webhook->project);

        $webhook->delete();
    }
}
