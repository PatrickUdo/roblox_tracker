<?php

namespace App\Actions\Items;

use App\Enums\ItemStatus;
use App\Events\ItemTransitioned;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransitionItem
{
    public function handle(User $actor, Item $item, ItemStatus|string $status): Item
    {
        Gate::forUser($actor)->authorize('transition', $item);

        if (is_string($status)) {
            Validator::make(['status' => $status], ['status' => ['required', Rule::enum(ItemStatus::class)]])->validate();
            $status = ItemStatus::from($status);
        }

        return DB::transaction(function () use ($actor, $item, $status) {
            // Re-read under lock so two concurrent transitions cannot both pass the check.
            $from = Item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail()->status;

            if (! $from->canTransitionTo($status)) {
                $allowed = implode(', ', array_map(fn (ItemStatus $s) => $s->value, $from->allowedTransitions()));

                throw ValidationException::withMessages([
                    'status' => "Cannot move from {$from->value} to {$status->value}. Allowed: {$allowed}.",
                ]);
            }

            $item->status = $status;
            $item->closed_at = $status === ItemStatus::Closed ? now() : null;
            $item->save();

            ItemTransitioned::dispatch($item, $actor, $from, $status);

            return $item;
        });
    }
}
