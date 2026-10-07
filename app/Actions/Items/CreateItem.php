<?php

namespace App\Actions\Items;

use App\Enums\ItemPriority;
use App\Enums\ItemSource;
use App\Enums\ItemStatus;
use App\Events\ItemCreated;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateItem
{
    /**
     * @param  array<string, mixed>  $data  type, title, description?, priority?, assignee_id?, labels? (names)
     */
    public function handle(User $actor, Project $project, array $data, ItemSource $source): Item
    {
        Gate::forUser($actor)->authorize('createItem', $project);

        $data['priority'] ??= ItemPriority::Medium->value;

        $validated = Validator::make($data, ItemRules::for($project))->validate();

        return DB::transaction(function () use ($actor, $project, $validated, $source) {
            $item = new Item([
                ...Arr::except($validated, 'labels'),
                'status' => ItemStatus::Open,
                'reporter_id' => $actor->id,
                'source' => $source,
            ]);
            $item->project()->associate($project);
            $item->save();

            $item->labels()->sync($project->labels()->whereIn('name', $validated['labels'] ?? [])->pluck('id'));

            ItemCreated::dispatch($item, $actor);

            return $item;
        });
    }
}
