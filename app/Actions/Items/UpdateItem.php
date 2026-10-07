<?php

namespace App\Actions\Items;

use App\Events\ItemUpdated;
use App\Models\Item;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * Changes fields; status changes go through TransitionItem.
 */
class UpdateItem
{
    /**
     * @param  array<string, mixed>  $data  type?, title?, description?, priority?, assignee_id?, labels? (names)
     */
    public function handle(User $actor, Item $item, array $data): Item
    {
        Gate::forUser($actor)->authorize('update', $item);

        $rules = array_map(fn (array $rules) => ['sometimes', ...$rules], ItemRules::for($item->project));
        $validated = Validator::make($data, $rules)->validate();

        return DB::transaction(function () use ($actor, $item, $validated) {
            $item->fill(Arr::except($validated, 'labels'));

            $old = [];
            $new = [];

            foreach (array_keys($item->getDirty()) as $field) {
                $old[$field] = $this->plain($item->getOriginal($field));
                $new[$field] = $this->plain($item->getAttribute($field));
            }

            $item->save();

            if (array_key_exists('labels', $validated)) {
                $before = $item->labels()->orderBy('name')->pluck('name')->all();
                $item->labels()->sync($item->project->labels()->whereIn('name', $validated['labels'])->pluck('id'));
                $after = $item->labels()->orderBy('name')->pluck('name')->all();

                if ($before !== $after) {
                    $old['labels'] = $before;
                    $new['labels'] = $after;
                }
            }

            if ($new !== []) {
                ItemUpdated::dispatch($item, $actor, $old, $new);
            }

            return $item;
        });
    }

    private function plain(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
