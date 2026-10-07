<?php

namespace App\Events;

use App\Models\Item;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ItemCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Item $item,
        public ?User $actor,
    ) {}
}
