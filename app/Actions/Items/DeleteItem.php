<?php

namespace App\Actions\Items;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteItem
{
    public function handle(User $actor, Item $item): void
    {
        Gate::forUser($actor)->authorize('delete', $item);

        $item->delete();
    }
}
