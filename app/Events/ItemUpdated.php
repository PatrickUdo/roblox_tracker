<?php

namespace App\Events;

use App\Models\Item;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ItemUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function __construct(
        public Item $item,
        public ?User $actor,
        public array $old,
        public array $new,
    ) {}
}
