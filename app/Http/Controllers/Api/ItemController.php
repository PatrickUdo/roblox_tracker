<?php

namespace App\Http\Controllers\Api;

use App\Actions\Items\CreateItem;
use App\Actions\Items\DeleteItem;
use App\Actions\Items\TransitionItem;
use App\Actions\Items\UpdateItem;
use App\Enums\ItemSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexItemsRequest;
use App\Http\Requests\Api\StoreItemRequest;
use App\Http\Requests\Api\TransitionItemRequest;
use App\Http\Requests\Api\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Queries\SearchItems;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    /**
     * List items in a project
     */
    public function index(IndexItemsRequest $request, Project $project, SearchItems $search): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $items = $search->query($user, $project, $request->filters())
            ->cursorPaginate((int) $request->input('per_page', 25))
            ->withQueryString();

        return ItemResource::collection($items);
    }

    /**
     * Create an item
     */
    public function store(StoreItemRequest $request, Project $project, CreateItem $create): ItemResource
    {
        /** @var User $user */
        $user = $request->user();
        $item = $create->handle($user, $project, $request->validated(), ItemSource::Api);

        return ItemResource::make($item->load(['project', 'reporter', 'assignee', 'labels']));
    }

    /**
     * Show an item
     *
     * Includes comments and the activity log.
     */
    public function show(Item $item): ItemResource
    {
        Gate::authorize('view', $item);

        return ItemResource::make($item->load([
            'project', 'reporter', 'assignee', 'labels', 'comments.user', 'activities.user',
        ]));
    }

    /**
     * Update an item
     *
     * Status cannot be changed here; use the transition endpoint.
     */
    public function update(UpdateItemRequest $request, Item $item, UpdateItem $update): ItemResource
    {
        /** @var User $user */
        $user = $request->user();

        return ItemResource::make($update->handle($user, $item, $request->validated())->load(['project', 'reporter', 'assignee', 'labels']));
    }

    /**
     * Change an item's status
     *
     * Returns 422 for a transition that is not allowed; `allowed_transitions` on the item lists the valid ones.
     */
    public function transition(TransitionItemRequest $request, Item $item, TransitionItem $transition): ItemResource
    {
        /** @var User $user */
        $user = $request->user();

        return ItemResource::make($transition->handle($user, $item, (string) $request->validated('status'))->load(['project', 'reporter', 'assignee', 'labels']));
    }

    /**
     * Delete an item
     *
     * Allowed for the reporter and project owners.
     */
    public function destroy(Request $request, Item $item, DeleteItem $delete): Response
    {
        /** @var User $user */
        $user = $request->user();
        $delete->handle($user, $item);

        return response()->noContent();
    }
}
