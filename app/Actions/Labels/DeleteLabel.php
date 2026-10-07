<?php

namespace App\Actions\Labels;

use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteLabel
{
    public function handle(User $actor, Label $label): void
    {
        Gate::forUser($actor)->authorize('manage', $label->project);

        $label->delete();
    }
}
