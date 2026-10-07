<?php

namespace App\Observers;

use App\Models\Item;

class ItemObserver
{
    public function creating(Item $item): void
    {
        $item->number ??= $item->project->allocateNumber();
    }
}
